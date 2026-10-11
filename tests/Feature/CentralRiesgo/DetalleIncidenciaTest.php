<?php

/**
 * @file    DetalleIncidenciaTest.php
 *
 * @author  Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 *
 * @description Pruebas HTTP con SQLite en memoria, sin usar Supabase, del
 * endpoint GET /api/incidencias/{idIncidencia}: datos completos del detalle
 * y error controlado cuando la incidencia no existe.
 *
 * @changelog
 * - 2026-10-09  [Alisson D. Alvarado]  test: cubrir detalle encontrado, 404 e invitado.
 *
 * @see App\Http\Controllers\Api\DetalleIncidenciaController
 * @see App\Services\CentralRiesgo\ConsultarDetalleIncidenciaService
 */

namespace Tests\Feature\CentralRiesgo;

use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DetalleIncidenciaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.incidencias_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('incidencias_pruebas');
        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        DB::purge('incidencias_pruebas');
        parent::tearDown();
    }

    /** @throws \Throwable Si el detalle no trae todos los campos del contrato. */
    public function test_devuelve_el_detalle_completo_de_la_incidencia(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web')
            ->getJson('/api/incidencias/1')
            ->assertOk()
            ->assertExactJson([
                'datos' => [
                    'id_registro' => 1,
                    'estudiante_nombre' => 'María Gómez',
                    'sis_estudiante' => '20181111111',
                    'motivo' => 'Copia o intercambio de respuestas',
                    'materia' => 'Matemática Discreta',
                    'reportado_por' => 'Luis Rojas',
                    'reportado_por_rol' => 'auxiliar',
                    'fecha_registro' => '2026-10-05 10:30:00',
                    'estado' => 'Confirmado',
                    'descripcion' => 'Copia visible desde el celular en el momento del examen.',
                ],
                'mensaje' => 'Detalle de la incidencia obtenido correctamente.',
            ]);
    }

    /** @throws \Throwable Si una incidencia inexistente no responde con error controlado. */
    public function test_devuelve_404_con_mensaje_cuando_la_incidencia_no_existe(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web')
            ->getJson('/api/incidencias/999')
            ->assertNotFound()
            ->assertExactJson(['mensaje' => 'La incidencia no existe.']);
    }

    /** @throws \Throwable Si un invitado puede leer el detalle de una incidencia. */
    public function test_requiere_autenticacion(): void
    {
        $this->getJson('/api/incidencias/1')->assertUnauthorized();
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
            $tabla->foreign('id_usuario')->references('id_usuario')->on('usuario');
            $tabla->foreign('id_rol')->references('id_rol')->on('rol');
        });
        Schema::create('estudiante', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante')->primary();
            $tabla->string('nombre_estudiante');
            $tabla->string('apellido_estudiante');
            $tabla->string('carrera')->nullable();
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->foreign('sis_doc')->references('id_usuario')->on('usuario');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
            $tabla->foreign('id_curso')->references('id_curso')->on('curso');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->integer('id_examen');
            $tabla->string('sis_estudiante');
            $tabla->integer('id_registrador');
            $tabla->string('motivo')->nullable();
            $tabla->string('detalle_motivo')->nullable();
            $tabla->dateTime('fecha_registro');
            $tabla->string('tipo_infraccion');
            $tabla->string('estado_incidencia')->default('Pendiente');
            $tabla->integer('id_confirmador')->nullable();
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
            $tabla->foreign('sis_estudiante')->references('sis_estudiante')->on('estudiante');
            $tabla->foreign('id_registrador')->references('id_usuario')->on('usuario');
        });
    }

    private function crearDatos(): void
    {
        DB::table('usuario')->insert([
            ['id_usuario' => 1, 'cod_sis' => 'DOC1', 'password' => 'solo-pruebas', 'nombre_usuario' => 'Ana', 'apellido' => 'Perez'],
            ['id_usuario' => 2, 'cod_sis' => 'AUX1', 'password' => 'solo-pruebas', 'nombre_usuario' => 'Luis', 'apellido' => 'Rojas'],
        ]);
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => 1, 'id_rol' => 1], ['id_usuario' => 2, 'id_rol' => 2],
        ]);
        DB::table('estudiante')->insert([
            'sis_estudiante' => '20181111111',
            'nombre_estudiante' => 'María',
            'apellido_estudiante' => 'Gómez',
            'carrera' => 'Ing. de Sistemas',
        ]);
        DB::table('curso')->insert([
            'id_curso' => 1, 'nombre_curso' => 'Matemática Discreta', 'sis_doc' => 1,
        ]);
        DB::table('examen')->insert(['id_examen' => 1]);
        DB::table('examen_curso')->insert(['id_examen' => 1, 'id_curso' => 1]);
        DB::table('central_riesgo')->insert([
            'id_registro' => 1,
            'id_examen' => 1,
            'sis_estudiante' => '20181111111',
            'id_registrador' => 2,
            'motivo' => 'copia_o_intercambio_de_respuestas',
            'detalle_motivo' => 'Copia visible desde el celular en el momento del examen.',
            'fecha_registro' => '2026-10-05 10:30:00',
            'tipo_infraccion' => 'tramposo',
            'estado_incidencia' => 'Confirmado',
            'id_confirmador' => null,
        ]);
    }
}