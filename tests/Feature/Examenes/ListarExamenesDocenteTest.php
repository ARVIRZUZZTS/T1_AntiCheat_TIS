<?php

/**
 * @file    ListarExamenesDocenteTest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-14
 *
 * @description
 * Pruebas del endpoint GET /api/examenes, que alimenta el listado global y los
 * selectores de la pagina unica de Examenes. Comprueba que entren todos los
 * examenes propios o compartidos con una invitacion aceptada, sin descartar los
 * pasados, y que cada examen llegue con los cursos que necesita el nombre
 * "tipo - curso".
 *
 * Usa una base SQLite en memoria, sin tocar la base compartida del .env.
 *
 * @see App\Http\Controllers\Api\ExamenDocenteController
 * @see App\Services\Examen\ListarExamenesDocenteService
 */

namespace Tests\Feature\Examenes;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ListarExamenesDocenteTest extends TestCase
{
    private const DOCENTE = 10;

    private const OTRO_DOCENTE = 20;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.examenes_docente_pruebas' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('examenes_docente_pruebas');
        $this->travelTo(Carbon::parse('2026-11-14 12:00:00'));
        $this->crearEsquema();
        $this->crearCatalogos();
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('examenes_docente_pruebas');

        parent::tearDown();
    }

    public function test_lista_todos_los_examenes_propios_o_con_invitacion_aceptada(): void
    {
        $this->crearExamen(1, self::DOCENTE, '2026-11-15', [1]);
        $this->crearExamen(2, self::OTRO_DOCENTE, '2026-11-16', [1]);
        $this->crearExamen(3, self::OTRO_DOCENTE, '2026-11-16', [1]);
        $this->crearExamen(4, self::OTRO_DOCENTE, '2026-11-16', [1]);
        $this->crearExamen(5, self::DOCENTE, '2026-11-13', [1]);
        $this->crearExamen(6, self::OTRO_DOCENTE, '2026-11-17', [1]);
        $this->crearExamen(7, self::DOCENTE, '2026-11-17', [1, 2]);
        $this->crearExamen(8, self::OTRO_DOCENTE, '2026-11-10', [1]);

        DB::table('invitacion_examen_compartido')->insert([
            [
                'id_examen' => 2,
                'id_docente_invitador' => self::OTRO_DOCENTE,
                'id_docente_invitado' => self::DOCENTE,
                'estado' => 'aceptado',
            ],
            [
                'id_examen' => 3,
                'id_docente_invitador' => self::OTRO_DOCENTE,
                'id_docente_invitado' => self::DOCENTE,
                'estado' => 'pendiente',
            ],
            [
                'id_examen' => 4,
                'id_docente_invitador' => self::OTRO_DOCENTE,
                'id_docente_invitado' => self::DOCENTE,
                'estado' => 'rechazado',
            ],
            [
                'id_examen' => 1,
                'id_docente_invitador' => self::DOCENTE,
                'id_docente_invitado' => self::OTRO_DOCENTE,
                'estado' => 'aceptado',
            ],
            [
                'id_examen' => 6,
                'id_docente_invitador' => self::OTRO_DOCENTE,
                'id_docente_invitado' => self::DOCENTE,
                'estado' => 'aceptado',
            ],
            [
                'id_examen' => 8,
                'id_docente_invitador' => self::OTRO_DOCENTE,
                'id_docente_invitado' => self::DOCENTE,
                'estado' => 'aceptado',
            ],
        ]);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE), 'web');

        $respuesta = $this->getJson('/api/examenes')->assertOk();

        $this->assertSame([7, 6, 2, 1, 5, 8], array_column($respuesta->json('datos'), 'id'));
        $this->assertSame(
            ['Calculo 1, Fisica 1', 'Calculo 1', 'Calculo 1', 'Calculo 1', 'Calculo 1', 'Calculo 1'],
            array_column($respuesta->json('datos'), 'curso')
        );
        $this->assertSame('Programado', $respuesta->json('datos.0.estado'));
        $this->assertSame('Finalizado', $respuesta->json('datos.4.estado'));
    }

    /** Esquema mínimo de las relaciones y del guard de autenticación. */
    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis')->nullable();
            $tabla->string('password')->nullable();
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
        });
        Schema::create('tipo_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_examen')->primary();
            $tabla->string('nombre_tipo_examen');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->string('nombre_examen')->nullable();
            $tabla->date('fecha')->nullable();
            $tabla->time('hora_inicio')->nullable();
            $tabla->integer('duracion')->nullable();
            $tabla->integer('creador');
            $tabla->integer('tipo_examen');
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
            $tabla->primary(['id_examen', 'id_curso']);
        });
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente');
        });
        Schema::create('examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_ambiente');
            $tabla->primary(['id_examen', 'id_ambiente']);
        });
        Schema::create('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->primary();
            $tabla->integer('id_examen');
            $tabla->string('sis_estudiante');
        });
        Schema::create('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_ingreso')->primary();
            $tabla->integer('id_examen');
            $tabla->string('id_estudiante');
        });
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_docente_invitador');
            $tabla->integer('id_docente_invitado');
            $tabla->string('estado');
        });
    }

    private function crearCatalogos(): void
    {
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => '10', 'password' => ''],
            ['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => '20', 'password' => ''],
        ]);
        DB::table('rol')->insert(['id_rol' => 1, 'nombre_rol' => 'docente']);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::OTRO_DOCENTE, 'id_rol' => 1],
        ]);
        DB::table('curso')->insert([
            ['id_curso' => 1, 'nombre_curso' => 'Calculo 1'],
            ['id_curso' => 2, 'nombre_curso' => 'Fisica 1'],
        ]);
        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => 1,
            'nombre_tipo_examen' => 'examen parcial',
        ]);
    }

    /**
     * @param  list<int>  $cursos
     */
    private function crearExamen(int $id, int $creador, string $fecha, array $cursos): void
    {
        DB::table('examen')->insert([
            'id_examen' => $id,
            'nombre_examen' => 'Examen parcial',
            'fecha' => $fecha,
            'hora_inicio' => '08:00:00',
            'duracion' => 120,
            'creador' => $creador,
            'tipo_examen' => 1,
        ]);

        foreach ($cursos as $idCurso) {
            DB::table('examen_curso')->insert([
                'id_examen' => $id,
                'id_curso' => $idCurso,
            ]);
        }
    }
}
