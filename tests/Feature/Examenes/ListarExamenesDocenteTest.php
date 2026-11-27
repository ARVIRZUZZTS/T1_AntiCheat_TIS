<?php

/**
 * @file    ListarExamenesDocenteTest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-14
 *
 * @updated 2026-11-24
 *
 * @description
 * Pruebas del endpoint GET /api/examenes, que alimenta el listado global y los
 * selectores de la pagina unica de Examenes. Comprueba que entren los examenes
 * que estan en un curso propio del docente (los que creo y los compartidos que
 * acepto y asocio a un curso suyo), sin descartar los pasados, y que la
 * etiqueta del examen muestre solo los cursos propios.
 *
 * Usa una base SQLite en memoria, sin tocar la base compartida del .env.
 *
 * @changelog
 * - 2026-11-24  [Valery D. Ortuno P]  test: solo examenes en un curso propio del
 *   docente y etiqueta con sus cursos; `curso.sis_doc` y `central_riesgo` en el
 *   esquema sqlite.
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

    public function test_lista_los_examenes_que_estan_en_un_curso_propio(): void
    {
        // Cursos 1 y 3 son del docente; el 2 es del otro docente.
        $this->crearExamen(1, self::DOCENTE, '2026-11-15', [1]);
        $this->crearExamen(5, self::DOCENTE, '2026-11-13', [1]);
        $this->crearExamen(7, self::DOCENTE, '2026-11-17', [1, 3]);
        // Compartidos: aceptado y asociado a un curso propio (sale).
        $this->crearExamen(2, self::OTRO_DOCENTE, '2026-11-16', [2, 1]);
        $this->crearExamen(8, self::OTRO_DOCENTE, '2026-11-10', [2, 1]);
        // Pendiente, rechazado o aceptado sin asociar curso propio (no salen).
        $this->crearExamen(3, self::OTRO_DOCENTE, '2026-11-16', [2]);
        $this->crearExamen(4, self::OTRO_DOCENTE, '2026-11-16', [2]);
        $this->crearExamen(6, self::OTRO_DOCENTE, '2026-11-17', [2]);

        $invitacion = fn (int $examen, string $estado): array => [
            'id_examen' => $examen,
            'id_docente_invitador' => self::OTRO_DOCENTE,
            'id_docente_invitado' => self::DOCENTE,
            'estado' => $estado,
        ];
        DB::table('invitacion_examen_compartido')->insert([
            $invitacion(2, 'aceptado'),
            $invitacion(3, 'pendiente'),
            $invitacion(4, 'rechazado'),
            $invitacion(6, 'aceptado'),
            $invitacion(8, 'aceptado'),
        ]);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE), 'web');

        $respuesta = $this->getJson('/api/examenes')->assertOk();

        $this->assertSame([7, 2, 1, 5, 8], array_column($respuesta->json('datos'), 'id'));
        // Solo los cursos propios: el del otro docente no aparece en la etiqueta.
        $this->assertSame(
            ['Algebra, Calculo 1', 'Calculo 1', 'Calculo 1', 'Calculo 1', 'Calculo 1'],
            array_column($respuesta->json('datos'), 'curso')
        );
        $this->assertSame('Programado', $respuesta->json('datos.0.estado'));
        $this->assertSame('Finalizado', $respuesta->json('datos.3.estado'));
    }

    public function test_no_lista_examenes_cuyos_cursos_estan_archivados(): void
    {
        $this->crearExamen(1, self::DOCENTE, '2026-11-15', [1]);
        $this->crearExamen(2, self::DOCENTE, '2026-11-16', [3]);
        DB::table('curso')->where('id_curso', 1)->update(['estado' => 'Archivado']);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE), 'web');

        $this->getJson('/api/examenes')
            ->assertOk()
            ->assertJsonCount(1, 'datos')
            ->assertJsonPath('datos.0.id', 2);
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
            // Docente dueño del curso: el listado filtra por los cursos propios.
            $tabla->integer('sis_doc')->nullable();
            $tabla->string('estado')->default('EnCurso');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->integer('id_examen')->nullable();
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
            ['id_curso' => 1, 'nombre_curso' => 'Calculo 1', 'sis_doc' => self::DOCENTE],
            ['id_curso' => 2, 'nombre_curso' => 'Fisica 1', 'sis_doc' => self::OTRO_DOCENTE],
            ['id_curso' => 3, 'nombre_curso' => 'Algebra', 'sis_doc' => self::DOCENTE],
        ]);
        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => 1,
            'nombre_tipo_examen' => 'examen primer parcial',
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
