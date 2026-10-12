<?php

/**
 * @file    NotificacionCentralRiesgoTest.php
 *
 * @author  David E. Chavez T. <virzuzz12345@gmail.com>
 *
 * @created 2026-10-11
 *
 * @description
 * Pruebas de la HU 6 · T1: cuando un estudiante queda registrado o confirmado
 * como "Tramposo" en la central de riesgos, se genera el aviso "Estudiante
 * ingresado a central de riesgo" para cada docente que lo tiene en una materia
 * activa, y NO para cursos finalizados ni para docentes sin el estudiante.
 *
 * Reglas cubiertas:
 * - un aviso por curso activo (`estado = EnCurso`) del estudiante;
 * - el reparto es idempotente (registrar y confirmar el mismo caso no duplica);
 * - un sospechoso reportado por el auxiliar no genera el aviso hasta que el
 *   docente confirma el caso; si lo rechaza, no queda ningún aviso;
 * - un auxiliar nunca recibe avisos.
 *
 * Igual que las demás pruebas de notificaciones, el esquema se arma sobre
 * SQLite en memoria con una conexión dedicada: NO se toca la base compartida
 * de Supabase. Se ejecutan las migraciones de `oculta` y de `tipo`.
 *
 * @see  App\Services\Notificacion\NotificacionService
 * @see  App\Services\CentralRiesgo\ResolverIncidenciaService
 *
 * @changelog
 * - 2026-10-11  [David E. Chavez T.]  test: criterios de la HU 6 (T1).
 */

namespace Tests\Feature\Notificaciones;

use App\Models\CentralRiesgo;
use App\Models\Usuario;
use App\Services\CentralRiesgo\ResolverIncidenciaService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificacionCentralRiesgoTest extends TestCase
{
    private const DOCENTE_A = 1;

    private const DOCENTE_B = 2;

    private const AUXILIAR = 3;

    /** Curso del docente A: activo y con el estudiante inscrito. */
    private const CURSO_A = 1;

    /** Curso del docente B: activo y con el estudiante inscrito. */
    private const CURSO_B = 2;

    /** Curso activo, pero el estudiante no cursa ahí. */
    private const CURSO_SIN_ESTUDIANTE = 3;

    /** Curso finalizado: el estudiante cursa, pero ya no es materia activa. */
    private const CURSO_FINALIZADO = 4;

    private const SIS_ESTUDIANTE = '900000001';

    private const SIS_SIN_CURSOS = '900000002';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.central_riesgo_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('central_riesgo_pruebas');

        $this->crearEsquema();

        (require database_path('migrations/2026_10_11_000001_agregar_oculta_a_notificacion_docente.php'))->up();
        (require database_path('migrations/2026_10_11_000002_agregar_tipo_a_notificacion_docente.php'))->up();

        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        DB::purge('central_riesgo_pruebas');
        parent::tearDown();
    }

    public function test_registrar_tramposo_avisa_a_los_docentes_de_las_materias_activas(): void
    {
        $registro = $this->registroCentral(tipo: 'tramposo', estado: 'Pendiente');

        $avisos = app(NotificacionService::class)->notificarCentralRiesgo($registro);

        $this->assertCount(2, $avisos);

        // Un aviso por materia activa: curso A y curso B, nunca el finalizado.
        $this->assertDatabaseHas('notificacion_docente', [
            'id_notificacion' => 1, 'id_central_riesgo' => 1, 'id_curso' => self::CURSO_A,
            'tipo' => 'central_riesgo', 'estado' => 'recibido', 'oculta' => false,
        ]);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_notificacion' => 2, 'id_central_riesgo' => 1, 'id_curso' => self::CURSO_B,
            'tipo' => 'central_riesgo', 'estado' => 'recibido', 'oculta' => false,
        ]);
        $this->assertDatabaseMissing('notificacion_docente', ['id_curso' => self::CURSO_FINALIZADO]);
        $this->assertDatabaseMissing('notificacion_docente', ['id_curso' => self::CURSO_SIN_ESTUDIANTE]);
        $this->assertDatabaseCount('notificacion_docente', 2);
    }

    public function test_cada_docente_solo_ve_el_aviso_de_su_materia(): void
    {
        $registro = $this->registroCentral(tipo: 'tramposo', estado: 'Pendiente');
        app(NotificacionService::class)->notificarCentralRiesgo($registro);

        $delDocenteA = app(NotificacionService::class)->listarPara(Usuario::findOrFail(self::DOCENTE_A));
        $delDocenteB = app(NotificacionService::class)->listarPara(Usuario::findOrFail(self::DOCENTE_B));

        $this->assertCount(1, $delDocenteA);
        $this->assertSame(self::CURSO_A, (int) DB::table('notificacion_docente')
            ->where('id_notificacion', $delDocenteA->first()['id'])->value('id_curso'));
        $this->assertSame('Matemática I', $delDocenteA->first()['materia']);

        $this->assertCount(1, $delDocenteB);
        $this->assertSame('Física II', $delDocenteB->first()['materia']);
    }

    public function test_un_auxiliar_no_recibe_avisos_de_ingreso(): void
    {
        $registro = $this->registroCentral(tipo: 'tramposo', estado: 'Pendiente');
        app(NotificacionService::class)->notificarCentralRiesgo($registro);

        $this->assertSame([], app(NotificacionService::class)->listarPara(Usuario::findOrFail(self::AUXILIAR))->all());
    }

    public function test_confirmar_un_sospechoso_genera_el_aviso_de_ingreso(): void
    {
        $registro = $this->registroCentral(id: 2, tipo: 'sospechoso', estado: 'Pendiente');

        // Sin confirmar, el reporte del auxiliar todavía no está en la central.
        $this->assertSame([], app(NotificacionService::class)->notificarCentralRiesgo($registro));
        $this->assertDatabaseCount('notificacion_docente', 0);

        $servicio = new ResolverIncidenciaService;
        $servicio->confirmar(2, Usuario::findOrFail(self::DOCENTE_A));

        $this->assertDatabaseCount('notificacion_docente', 2);
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 2, 'id_curso' => self::CURSO_A, 'tipo' => 'central_riesgo']);
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 2, 'id_curso' => self::CURSO_B, 'tipo' => 'central_riesgo']);

        // El caso queda confirmado y con su aviso vinculado.
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 2, 'estado_incidencia' => 'Confirmado']);
    }

    public function test_rechazar_el_caso_no_deja_avisos(): void
    {
        $registro = $this->registroCentral(id: 3, tipo: 'sospechoso', estado: 'Pendiente');
        app(NotificacionService::class)->notificarCentralRiesgo($registro, porConfirmacion: true);
        $this->assertDatabaseCount('notificacion_docente', 2);

        (new ResolverIncidenciaService)->rechazar(3, Usuario::findOrFail(self::DOCENTE_A));

        $this->assertDatabaseMissing('central_riesgo', ['id_registro' => 3]);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    public function test_no_duplica_avisos_del_mismo_caso(): void
    {
        $registro = $this->registroCentral(tipo: 'tramposo', estado: 'Pendiente');
        $servicio = app(NotificacionService::class);

        $servicio->notificarCentralRiesgo($registro);
        $segundos = $servicio->notificarCentralRiesgo($registro);

        $this->assertSame([], $segundos);
        $this->assertDatabaseCount('notificacion_docente', 2);
    }

    public function test_estudiante_sin_materias_activas_no_genera_avisos(): void
    {
        $registro = $this->registroCentral(id: 4, sis: self::SIS_SIN_CURSOS, tipo: 'tramposo', estado: 'Pendiente');

        $this->assertSame([], app(NotificacionService::class)->notificarCentralRiesgo($registro));
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    private function registroCentral(int $id = 1, string $sis = self::SIS_ESTUDIANTE, string $tipo = 'tramposo', string $estado = 'Pendiente'): CentralRiesgo
    {
        DB::table('central_riesgo')->insert([
            'id_registro' => $id,
            'sis_estudiante' => $sis,
            'id_examen' => 1,
            'id_registrador' => self::AUXILIAR,
            'tipo_infraccion' => $tipo,
            'estado_incidencia' => $estado,
            'fecha_registro' => Carbon::now()->toDateTimeString(),
        ]);

        return CentralRiesgo::query()->findOrFail($id);
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
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('fecha_creacion')->nullable();
            $tabla->string('estado')->nullable();
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->string('fecha')->nullable();
            $tabla->string('hora_inicio')->nullable();
            $tabla->integer('duracion')->nullable();
            $tabla->integer('creador')->nullable();
            $tabla->integer('tipo_examen')->nullable();
        });
        Schema::create('examen_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_curso');
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
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_registrador');
            $tabla->integer('id_confirmador')->nullable();
            $tabla->string('motivo')->nullable();
            $tabla->string('detalle_motivo')->nullable();
            $tabla->dateTime('fecha_registro')->nullable();
            $tabla->string('tipo_infraccion');
            $tabla->string('estado_incidencia')->default('Pendiente');
        });
        Schema::create('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_notificacion')->primary();
            $tabla->integer('id_central_riesgo');
            $tabla->integer('id_curso');
            $tabla->string('estado');
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'cod_sis' => 'DOCA', 'password' => 'x', 'nombre_usuario' => 'Doc', 'apellido' => 'A'],
            ['id_usuario' => self::DOCENTE_B, 'cod_sis' => 'DOCB', 'password' => 'x', 'nombre_usuario' => 'Doc', 'apellido' => 'B'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX', 'password' => 'x', 'nombre_usuario' => 'Aux', 'apellido' => 'Uno'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'id_rol' => 1],
            ['id_usuario' => self::DOCENTE_B, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
        ]);
        DB::table('curso')->insert([
            ['id_curso' => self::CURSO_A, 'nombre_curso' => 'Matemática I', 'sis_doc' => self::DOCENTE_A, 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_B, 'nombre_curso' => 'Física II', 'sis_doc' => self::DOCENTE_B, 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_SIN_ESTUDIANTE, 'nombre_curso' => 'Química', 'sis_doc' => self::DOCENTE_A, 'estado' => 'EnCurso'],
            ['id_curso' => self::CURSO_FINALIZADO, 'nombre_curso' => 'Álgebra IV', 'sis_doc' => self::DOCENTE_B, 'estado' => 'Finalizo'],
        ]);
        DB::table('examen')->insert([
            ['id_examen' => 1, 'tipo_examen' => null],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => 1, 'id_curso' => self::CURSO_A],
        ]);
        DB::table('estudiante')->insert([
            ['sis_estudiante' => self::SIS_ESTUDIANTE, 'nombre_estudiante' => 'Juan', 'apellido_estudiante' => 'Quispe'],
            ['sis_estudiante' => self::SIS_SIN_CURSOS, 'nombre_estudiante' => 'Ana', 'apellido_estudiante' => 'Rojas'],
        ]);
        DB::table('estudiante_curso')->insert([
            ['sis_estudiante' => self::SIS_ESTUDIANTE, 'id_curso' => self::CURSO_A],
            ['sis_estudiante' => self::SIS_ESTUDIANTE, 'id_curso' => self::CURSO_B],
            ['sis_estudiante' => self::SIS_ESTUDIANTE, 'id_curso' => self::CURSO_FINALIZADO],
        ]);
    }
}
