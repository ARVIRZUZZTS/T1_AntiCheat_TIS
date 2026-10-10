<?php

/**
 * @file    ListarExamenesCursoTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description
 * Pruebas HTTP de la task #138 con Eloquent y una base SQLite en memoria.
 * Se configura una conexión propia antes de crear datos: nunca se ejecutan
 * migraciones ni escrituras en la base Supabase compartida del .env.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  test: cubrir contrato, cursos y ordenamiento.
 * - 2026-10-10  [Valery D. Ortuno P]  test: adaptar los tipos de examen al
 *   catálogo real (examen parcial / examen final) tras alinear el enum.
 *
 * @see App\Http\Controllers\Api\CursoExamenController
 * @see App\Services\Examen\ListarExamenesCursoService
 */

namespace Tests\Feature\Examenes;

use App\Services\Examen\ListarExamenesCursoService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Valida el endpoint real sin depender de datos ni credenciales de Supabase. */
class ListarExamenesCursoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.examenes_pruebas' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('examenes_pruebas');
        $this->travelTo(Carbon::parse('2026-10-09 12:00:00'));
        $this->crearEsquema();

        DB::table('curso')->insert([
            ['id_curso' => 1, 'nombre_curso' => 'Sistemas'],
            ['id_curso' => 2, 'nombre_curso' => 'Otro curso'],
        ]);
        DB::table('tipo_examen')->insert([
            ['id_tipo_examen' => 1, 'nombre_tipo_examen' => 'examen parcial'],
            ['id_tipo_examen' => 2, 'nombre_tipo_examen' => 'examen final'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('examenes_pruebas');

        parent::tearDown();
    }

    /**
     * @throws \Throwable Si el endpoint incumple el contrato o consulta otro curso.
     */
    public function test_devuelve_tipo_horario_ambientes_e_ingresos_solo_del_curso(): void
    {
        $this->crearExamen(1, ['tipo_examen' => 2]);
        $this->crearExamen(2, ['id_curso' => 2]);
        DB::table('ambiente')->insert([
            ['id_ambiente' => 1, 'nombre_ambiente' => 'Aula 101'],
            ['id_ambiente' => 2, 'nombre_ambiente' => 'Laboratorio'],
        ]);
        DB::table('examen_ambiente')->insert([
            ['id_examen' => 1, 'id_ambiente' => 2],
            ['id_examen' => 1, 'id_ambiente' => 1],
        ]);
        DB::table('estudiante_examen')->insert([
            ['id_estudiante_examen' => 1, 'id_examen' => 1, 'sis_estudiante' => '10001'],
            ['id_estudiante_examen' => 2, 'id_examen' => 1, 'sis_estudiante' => '10002'],
            ['id_estudiante_examen' => 3, 'id_examen' => 1, 'sis_estudiante' => '10003'],
        ]);
        DB::table('registro_asistencia')->insert([
            ['id_ingreso' => 1, 'id_examen' => 1, 'id_estudiante' => '10001'],
            ['id_ingreso' => 2, 'id_examen' => 1, 'id_estudiante' => '10002'],
            ['id_ingreso' => 3, 'id_examen' => 1, 'id_estudiante' => '10001'],
            ['id_ingreso' => 4, 'id_examen' => 2, 'id_estudiante' => '10003'],
        ]);

        $this->getJson('/api/cursos/1/examenes')->assertOk()->assertExactJson([
            'datos' => [[
                'id' => 1,
                'tipo' => 'Examen final',
                'estado' => 'Programado',
                'fecha' => '2026-10-10',
                'hora_inicio' => '08:00',
                'hora_fin' => '10:00',
                'duracion' => 120,
                'inscritos' => 3,
                'ingresados' => 2,
                'ambientes' => [
                    ['id' => 1, 'nombre' => 'Aula 101'],
                    ['id' => 2, 'nombre' => 'Laboratorio'],
                ],
            ]],
        ]);
    }

    /**
     * @throws \Throwable Si un examen ajeno aparece o el orden es incorrecto.
     */
    public function test_ordena_fecha_y_hora_descendentes_con_finalizados_al_final(): void
    {
        $this->crearExamen(1);
        $this->crearExamen(2, ['hora_inicio' => '14:00:00', 'hora_fin' => '16:00:00']);
        $this->crearExamen(3, ['fecha' => '2026-10-11']);
        $this->crearExamen(4, ['fecha' => '2026-10-09', 'hora_inicio' => '11:00:00', 'hora_fin' => '11:30:00']);
        $this->crearExamen(5, ['fecha' => '2026-10-09', 'hora_inicio' => '09:00:00', 'hora_fin' => '15:00:00']);
        $this->crearExamen(6, ['fecha' => '2026-10-08']);
        $this->crearExamen(7, ['fecha' => '2026-10-08', 'hora_inicio' => '14:00:00', 'hora_fin' => '16:00:00']);
        $this->crearExamen(8, ['fecha' => '2026-10-20', 'id_curso' => 2]);
        $this->crearExamen(9, ['hora_inicio' => '14:00:00', 'hora_fin' => '16:00:00']);

        $respuesta = $this->getJson('/api/cursos/1/examenes')->assertOk();

        $this->assertSame([3, 9, 2, 1, 5, 4, 7, 6], array_column($respuesta->json('datos'), 'id'));
        $this->assertSame(
            ['Programado', 'Programado', 'Programado', 'Programado', 'Programado',
                'Finalizado', 'Finalizado', 'Finalizado'],
            array_column($respuesta->json('datos'), 'estado')
        );
        // La vista existente conserva su tercer estado y recibe el mismo orden.
        $examenes = app(ListarExamenesCursoService::class)->ejecutar(1);
        $this->assertSame('en_curso', $examenes[4]['estado']);
    }

    /**
     * @throws \Throwable Si el listado pierde el examen vinculado a ambos cursos.
     */
    public function test_incluye_un_examen_compartido_en_ambos_cursos(): void
    {
        $this->crearExamen(1);
        DB::table('examen_curso')->insert(['id_examen' => 1, 'id_curso' => 2]);

        foreach ([1, 2] as $idCurso) {
            $this->getJson('/api/cursos/'.$idCurso.'/examenes')
                ->assertOk()->assertJsonCount(1, 'datos')->assertJsonPath('datos.0.id', 1);
        }
    }

    /**
     * @throws \Throwable Si una relación vacía produce null o un conteo incorrecto.
     */
    public function test_devuelve_ceros_y_ambientes_vacios_sin_registros(): void
    {
        $this->crearExamen(1);

        $this->getJson('/api/cursos/1/examenes')->assertOk()
            ->assertJsonPath('datos.0.tipo', 'Examen parcial')
            ->assertJsonPath('datos.0.inscritos', 0)
            ->assertJsonPath('datos.0.ingresados', 0)
            ->assertJsonPath('datos.0.ambientes', []);
    }

    /**
     * @throws \Throwable Si un curso válido sin exámenes no devuelve una lista vacía.
     */
    public function test_curso_sin_examenes_devuelve_lista_vacia(): void
    {
        $this->getJson('/api/cursos/1/examenes')->assertOk()->assertExactJson(['datos' => []]);
    }

    /**
     * @throws \Throwable Si se acepta un curso inexistente o un identificador inválido.
     */
    public function test_curso_inexistente_o_identificador_invalido_devuelve_404(): void
    {
        $this->getJson('/api/cursos/999/examenes')->assertNotFound();
        $this->getJson('/api/cursos/no-numerico/examenes')->assertNotFound();
    }

    /**
     * @throws \Throwable Si los valores opcionales impiden clasificar el examen.
     */
    public function test_examen_sin_fecha_ni_horarios_se_mantiene_programado(): void
    {
        $this->crearExamen(1, ['fecha' => null, 'hora_inicio' => null, 'hora_fin' => null]);

        $this->getJson('/api/cursos/1/examenes')->assertOk()
            ->assertJsonPath('datos.0.estado', 'Programado')
            ->assertJsonPath('datos.0.fecha', null)
            ->assertJsonPath('datos.0.hora_inicio', null)
            ->assertJsonPath('datos.0.hora_fin', null);
    }

    /** Esquema mínimo de las relaciones consultadas, independiente de las migraciones PostgreSQL. */
    private function crearEsquema(): void
    {
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
            $tabla->date('fecha')->nullable();
            $tabla->time('hora_inicio')->nullable();
            $tabla->time('hora_fin')->nullable();
            $tabla->integer('duracion')->nullable();
            $tabla->integer('tipo_examen');
            $tabla->foreign('tipo_examen')->references('id_tipo_examen')->on('tipo_examen');
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
            $tabla->primary(['id_examen', 'id_curso']);
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
            $tabla->foreign('id_curso')->references('id_curso')->on('curso');
        });
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente');
        });
        Schema::create('examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_ambiente');
            $tabla->primary(['id_examen', 'id_ambiente']);
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
            $tabla->foreign('id_ambiente')->references('id_ambiente')->on('ambiente');
        });
        Schema::create('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->primary();
            $tabla->integer('id_examen');
            $tabla->string('sis_estudiante');
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
        });
        Schema::create('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_ingreso')->primary();
            $tabla->integer('id_examen');
            $tabla->string('id_estudiante');
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
        });
    }

    /**
     * @param  int  $idExamen  ID dedicado al caso de prueba.
     * @param  array<string, mixed>  $atributos  Variaciones de fecha, horario, tipo o curso.
     */
    private function crearExamen(int $idExamen, array $atributos = []): void
    {
        $idCurso = $atributos['id_curso'] ?? 1;
        unset($atributos['id_curso']);
        DB::table('examen')->insert(array_merge([
            'id_examen' => $idExamen,
            'fecha' => '2026-10-10',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '10:00:00',
            'duracion' => 120,
            'tipo_examen' => 1,
        ], $atributos));
        DB::table('examen_curso')->insert(['id_examen' => $idExamen, 'id_curso' => $idCurso]);
    }
}
