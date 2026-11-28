<?php

/**
 * @file    FiltrosReportesTest.php
 *
 * @author  Alex Candia <alex.leonar.candia@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Cubre los selectores compartidos de Reportes. Cada subopción debe traer solo
 * los cursos y exámenes con datos del reporte correspondiente, con la gestión
 * en la etiqueta del curso. También verifica que no queden datos de prueba en
 * el HTML. Corre sobre SQLite en memoria para no depender de PostgreSQL.
 *
 * @see  App\Services\Reporte\ListarFiltrosReporteService
 * @see  resources/views/pages/reportes.blade.php
 */

namespace Tests\Feature\Reportes;

use App\Enums\Motivo;
use App\Enums\TipoInfraccion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FiltrosReportesTest extends TestCase
{
    private const DOCENTE = 997001;

    private const CURSO_INGRESO = 997101;

    private const CURSO_RIESGO = 997102;

    private const CURSO_SIN_DATOS = 997103;

    private const EXAMEN_FINALIZADO_CON_INGRESO = 997201;

    private const EXAMEN_FUTURO_CON_INGRESO = 997202;

    private const EXAMEN_FINALIZADO_SIN_INGRESO = 997203;

    private const EXAMEN_FINALIZADO_CON_RIESGO = 997204;

    private const FECHA_FINALIZADA = '2026-11-26';

    private const FECHA_FUTURA = '2026-11-28';

    private const FECHA_SIN_INGRESO = '2026-11-25';

    private const FECHA_RIESGO = '2026-11-24';

    private const GESTION = 'Segundo semestre 2026';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.reportes_pruebas' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::setDefaultConnection('reportes_pruebas');
        $this->travelTo(Carbon::parse('2026-11-27 12:00:00'));
        $this->crearEsquema();

        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => 997501,
            'nombre_tipo_examen' => 'examen primer parcial',
        ]);
        DB::table('tipo_gestion')->insert([
            'id_tipo_gestion' => 997601,
            'nombre_tipo_gestion' => 'segundo semestre',
        ]);

        $this->crearDocente();
        $this->crearCurso(self::CURSO_INGRESO, 'Curso con ingreso');
        $this->crearCurso(self::CURSO_RIESGO, 'Curso con riesgo');
        $this->crearCurso(self::CURSO_SIN_DATOS, 'Curso sin datos');

        $this->crearExamen(self::EXAMEN_FINALIZADO_CON_INGRESO, self::FECHA_FINALIZADA, [self::CURSO_INGRESO]);
        $this->crearExamen(self::EXAMEN_FUTURO_CON_INGRESO, self::FECHA_FUTURA, [self::CURSO_INGRESO]);
        $this->crearExamen(self::EXAMEN_FINALIZADO_SIN_INGRESO, self::FECHA_SIN_INGRESO, [self::CURSO_SIN_DATOS]);
        $this->crearExamen(self::EXAMEN_FINALIZADO_CON_RIESGO, self::FECHA_RIESGO, [self::CURSO_RIESGO]);

        $this->crearIngreso(997301, self::EXAMEN_FINALIZADO_CON_INGRESO);
        $this->crearIngreso(997302, self::EXAMEN_FUTURO_CON_INGRESO);
        $this->crearRiesgo(997401, self::EXAMEN_FINALIZADO_CON_RIESGO, self::CURSO_RIESGO);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        DB::purge('reportes_pruebas');

        parent::tearDown();
    }

    public function test_asistencia_solo_lista_cursos_y_examenes_finalizados_con_ingreso(): void
    {
        $this->get(route('reportes.asistencia'))
            ->assertOk()
            ->assertSee($this->jsonParaVista('Curso con ingreso — '.self::GESTION), false)
            ->assertSee($this->jsonParaVista($this->etiquetaExamen(self::FECHA_FINALIZADA)), false)
            ->assertDontSee($this->jsonParaVista('Curso con riesgo — '.self::GESTION), false)
            ->assertDontSee($this->jsonParaVista('Curso sin datos — '.self::GESTION), false)
            ->assertDontSee($this->jsonParaVista($this->etiquetaExamen(self::FECHA_FUTURA)), false);
    }

    public function test_horario_de_ingreso_solo_lista_cursos_y_examenes_finalizados_con_ingreso(): void
    {
        $this->get(route('reportes.horario-ingreso'))
            ->assertOk()
            ->assertSee($this->jsonParaVista('Curso con ingreso — '.self::GESTION), false)
            ->assertSee($this->jsonParaVista($this->etiquetaExamen(self::FECHA_FINALIZADA)), false)
            ->assertDontSee($this->jsonParaVista('Curso con riesgo — '.self::GESTION), false)
            ->assertDontSee($this->jsonParaVista('Curso sin datos — '.self::GESTION), false)
            ->assertDontSee($this->jsonParaVista($this->etiquetaExamen(self::FECHA_FUTURA)), false);
    }

    public function test_intentos_no_autorizados_solo_lista_cursos_y_examenes_con_riesgo(): void
    {
        $this->get(route('reportes.intentos-no-autorizados'))
            ->assertOk()
            ->assertSee($this->jsonParaVista('Curso con riesgo — '.self::GESTION), false)
            ->assertSee($this->jsonParaVista($this->etiquetaExamen(self::FECHA_RIESGO)), false)
            ->assertDontSee($this->jsonParaVista('Curso con ingreso — '.self::GESTION), false)
            ->assertDontSee($this->jsonParaVista('Curso sin datos — '.self::GESTION), false);
    }

    public function test_los_reportes_no_renderizan_datos_de_prueba(): void
    {
        foreach ([
            route('reportes.asistencia'),
            route('reportes.horario-ingreso'),
            route('reportes.intentos-no-autorizados'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('datosPrueba', false)
                ->assertDontSee('reportes_pruebas', false)
                ->assertDontSee('pruebas_reporte', false);
        }
    }

    private function crearDocente(): void
    {
        DB::table('usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'cod_sis' => 'FILTROS-DOC',
            'password' => 'x',
            'nombre_usuario' => 'Filtros',
            'apellido' => 'Docente',
            'estado_cuenta' => 'habilitada',
        ]);
        DB::table('rol_usuario')->insert([
            'id_usuario' => self::DOCENTE,
            'id_rol' => 1,
        ]);
    }

    private function crearCurso(int $idCurso, string $nombre): void
    {
        DB::table('curso')->insert([
            'id_curso' => $idCurso,
            'nombre_curso' => $nombre,
            'sis_doc' => self::DOCENTE,
            'estado' => 'EnCurso',
            'fecha_creacion' => '2026-01-01',
        ]);
        DB::table('curso_tipo_gestion')->insert([
            'id_curso' => $idCurso,
            'id_tg' => 997601,
            'anio' => 2026,
        ]);
    }

    private function crearExamen(int $idExamen, string $fecha, array $cursos): void
    {
        DB::table('examen')->insert([
            'id_examen' => $idExamen,
            'fecha' => $fecha,
            'hora_inicio' => '08:00:00',
            'duracion' => 120,
            'creador' => self::DOCENTE,
            'tipo_examen' => 997501,
        ]);

        foreach ($cursos as $idCurso) {
            DB::table('examen_curso')->insert([
                'id_examen' => $idExamen,
                'id_curso' => $idCurso,
            ]);
        }
    }

    private function crearIngreso(int $idIngreso, int $idExamen): void
    {
        DB::table('estudiante')->insertOrIgnore([
            'sis_estudiante' => '997001',
            'nombre_estudiante' => 'Estudiante',
            'apellido_estudiante' => 'Filtros',
            'carrera' => 'Sistemas',
        ]);
        DB::table('registro_asistencia')->insert([
            'id_ingreso' => $idIngreso,
            'hora_ingreso' => '08:05:00',
            'id_examen' => $idExamen,
            'id_estudiante' => '997001',
            'id_registrador' => self::DOCENTE,
        ]);
    }

    private function crearRiesgo(int $idRegistro, int $idExamen, int $idCurso): void
    {
        DB::table('central_riesgo')->insert([
            'id_registro' => $idRegistro,
            'sis_estudiante' => '997001',
            'id_examen' => $idExamen,
            'id_curso' => $idCurso,
            'id_registrador' => self::DOCENTE,
            'motivo' => Motivo::Otro->value,
            'detalle_motivo' => 'Intento no autorizado de prueba',
            'fecha_registro' => '2026-11-24 08:20:00',
            'tipo_infraccion' => TipoInfraccion::Tramposo->value,
        ]);
    }

    private function etiquetaExamen(string $fecha): string
    {
        return 'Examen primer parcial — '.Carbon::parse($fecha)->format('d/m/Y');
    }

    private function jsonParaVista(string $texto): string
    {
        $banderas = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $primero = substr((string) json_encode($texto, $banderas), 1, -1);
        $segundo = substr((string) json_encode($primero, $banderas), 1, -1);

        return $segundo;
    }

    private function crearEsquema(): void
    {
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis')->nullable();
            $tabla->string('password')->nullable();
            $tabla->string('nombre_usuario')->nullable();
            $tabla->string('apellido')->nullable();
            $tabla->string('nombre')->nullable();
            $tabla->string('correo')->nullable();
            $tabla->string('estado_cuenta')->nullable();
            $tabla->boolean('puede_crear_auxiliares')->default(false);
            $tabla->timestamp('fecha_creacion')->nullable();
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
            $tabla->primary(['id_usuario', 'id_rol']);
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado')->default('EnCurso');
            $tabla->date('fecha_creacion')->nullable();
        });
        Schema::create('tipo_gestion', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_gestion')->primary();
            $tabla->string('nombre_tipo_gestion');
        });
        Schema::create('curso_tipo_gestion', function (Blueprint $tabla): void {
            $tabla->integer('id_curso');
            $tabla->integer('id_tg');
            $tabla->integer('anio')->nullable();
            $tabla->primary(['id_curso', 'id_tg']);
        });
        Schema::create('tipo_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_examen')->primary();
            $tabla->string('nombre_tipo_examen');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->date('fecha')->nullable();
            $tabla->string('hora_inicio')->nullable();
            $tabla->integer('duracion')->nullable();
            $tabla->integer('creador');
            $tabla->integer('tipo_examen');
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
            $tabla->primary(['id_examen', 'id_curso']);
        });
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_examen');
            $tabla->integer('id_docente_invitado');
            $tabla->string('estado');
            $tabla->timestamp('created_at')->nullable();
        });
        Schema::create('estudiante', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante')->primary();
            $tabla->string('nombre_estudiante');
            $tabla->string('apellido_estudiante');
            $tabla->string('carrera')->nullable();
        });
        Schema::create('estudiante_curso', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante');
            $tabla->integer('id_curso');
            $tabla->primary(['sis_estudiante', 'id_curso']);
        });
        Schema::create('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->primary();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_examen');
            $tabla->string('estado')->nullable();
        });
        Schema::create('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_ingreso')->primary();
            $tabla->string('hora_ingreso')->nullable();
            $tabla->integer('id_examen');
            $tabla->string('id_estudiante');
            $tabla->integer('id_registrador');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->string('sis_estudiante')->nullable();
            $tabla->integer('id_examen')->nullable();
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_registrador')->nullable();
            $tabla->string('motivo')->nullable();
            $tabla->string('detalle_motivo')->nullable();
            $tabla->timestamp('fecha_registro')->nullable();
            $tabla->string('tipo_infraccion')->nullable();
        });
    }
}
