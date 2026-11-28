<?php

/**
 * @file    EliminacionExamenNotificacionTest.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-28
 *
 * @description
 * Pruebas de los avisos «Examen eliminado» (`examen_eliminado`) que se generan
 * al desvincular un examen de un curso con `EliminarExamenCursoService`.
 * Verifican los destinatarios elegibles (auxiliares activos del curso y
 * docentes invitados que aceptaron), la exclusión de quien elimina, cuentas
 * eliminadas lógicamente, auxiliares en Baja, invitaciones no aceptadas, el
 * guardado de `id_curso` y `detalles`, la omisión al forzar desde eliminación
 * de curso y el formato devuelto por `NotificacionService::listarPara()`.
 *
 * El esquema se arma sobre SQLite en memoria con una conexión dedicada para no
 * tocar la base compartida.
 *
 * @see  App\Services\Examen\EliminarExamenCursoService
 * @see  App\Services\Examen\NotificarEliminacionExamenService
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-11-28  [OchoaCesar]  test: cobertura del aviso «Examen eliminado» al
 *   desvincular un examen de un curso.
 */

namespace Tests\Feature\Notificaciones;

use App\Enums\EstadoCuenta;
use App\Models\Curso;
use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Examen\EliminarExamenCursoService;
use App\Services\Examen\NotificarEliminacionExamenService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EliminacionExamenNotificacionTest extends TestCase
{
    private const DOCENTE = 1;

    private const INVITADO_ACEPTADO = 2;

    private const INVITADO_PENDIENTE = 3;

    private const AUX_ACTIVO = 4;

    private const AUX_BAJA = 5;

    private const AUX_ELIMINADO = 6;

    private const AUX_OTRO_CURSO = 7;

    private const CURSO = 20;

    private const OTRO_CURSO = 21;

    private const EXAMEN = 10;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-28 10:00:00');

        config(['database.connections.eliminacion_examen_notif_pruebas' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('eliminacion_examen_notif_pruebas');

        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('eliminacion_examen_notif_pruebas');
        parent::tearDown();
    }

    public function test_desvincular_examen_notifica_a_auxiliares_activos_y_docentes_compartidos(): void
    {
        $docente = Usuario::query()->findOrFail(self::DOCENTE);
        $examen = Examen::query()->findOrFail(self::EXAMEN);
        $curso = Curso::query()->findOrFail(self::CURSO);

        app(EliminarExamenCursoService::class)->ejecutar($docente, $examen, $curso);

        $this->assertDatabaseMissing('examen_curso', [
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
        ]);

        $destinatarios = DB::table('notificacion_docente')
            ->pluck('id_docente_destinatario')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $this->assertSame([self::INVITADO_ACEPTADO, self::AUX_ACTIVO], $destinatarios);

        foreach ($destinatarios as $id) {
            $this->assertDatabaseHas('notificacion_docente', [
                'id_docente_destinatario' => $id,
                'id_curso' => self::CURSO,
                'tipo' => NotificacionService::TIPO_EXAMEN_ELIMINADO,
                'estado' => NotificacionService::ESTADO_RECIBIDO,
                'oculta' => false,
                'fecha_evento' => '2026-11-28 10:00:00',
            ]);
        }

        $detalles = json_decode((string) DB::table('notificacion_docente')
            ->where('id_docente_destinatario', self::AUX_ACTIVO)
            ->value('detalles'), true);

        $this->assertSame(self::EXAMEN, $detalles['examen_id']);
        $this->assertSame('Examen primer parcial', $detalles['examen']);
        $this->assertSame(self::CURSO, $detalles['curso_id']);
        $this->assertSame('Calculo I', $detalles['curso']);
        $this->assertSame('Roberto Silva', $detalles['eliminado_por']);

        $aviso = app(NotificacionService::class)
            ->listarPara(Usuario::query()->findOrFail(self::AUX_ACTIVO))
            ->first();

        $this->assertNotNull($aviso);
        $this->assertSame(NotificacionService::TIPO_EXAMEN_ELIMINADO, $aviso['tipo']);
        $this->assertSame(NotificacionService::TITULO_EXAMEN_ELIMINADO, $aviso['titulo']);
        $this->assertSame(
            'El examen Examen primer parcial fue eliminado del curso Calculo I por Roberto Silva.',
            $aviso['descripcion'],
        );
        $this->assertSame(self::EXAMEN, $aviso['referencia']);
        $this->assertSame('2026-11-28 10:00:00', $aviso['fecha_registro']);
        $this->assertNotSame('', $aviso['hace']);
        $this->assertArrayHasKey('vence_en', $aviso);
        $this->assertNull($aviso['vence_en']);
    }

    public function test_eliminar_examen_forzado_desde_curso_no_genera_avisos(): void
    {
        $docente = Usuario::query()->findOrFail(self::DOCENTE);
        $examen = Examen::query()->findOrFail(self::EXAMEN);
        $curso = Curso::query()->findOrFail(self::CURSO);

        app(EliminarExamenCursoService::class)->ejecutar($docente, $examen, $curso, true);

        $this->assertDatabaseMissing('examen_curso', [
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
        ]);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    public function test_si_no_hay_destinatarios_elegibles_no_escribe_notificaciones(): void
    {
        DB::table('auxiliar_curso')->where('id_auxiliar', self::AUX_ACTIVO)->delete();
        DB::table('invitacion_examen_compartido')->where('id_docente_invitado', self::INVITADO_ACEPTADO)->delete();

        $avisos = app(NotificarEliminacionExamenService::class)->notificar(
            Examen::query()->findOrFail(self::EXAMEN),
            Curso::query()->findOrFail(self::CURSO),
            Usuario::query()->findOrFail(self::DOCENTE),
        );

        $this->assertSame([], $avisos);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
            $tabla->string('nombre_usuario');
            $tabla->string('apellido');
            $tabla->string('estado_cuenta')->default(EstadoCuenta::Habilitada->value);
        });
        Schema::create('rol', function (Blueprint $tabla): void {
            $tabla->integer('id_rol')->primary();
            $tabla->string('nombre_rol');
        });
        Schema::create('rol_usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario');
            $tabla->integer('id_rol');
        });
        Schema::create('tipo_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_examen')->primary();
            $tabla->string('nombre_tipo_examen');
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
            $tabla->string('estado')->default('EnCurso');
        });
        Schema::create('examen', function (Blueprint $tabla): void {
            $tabla->integer('id_examen')->primary();
            $tabla->string('nombre_examen')->nullable();
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
        Schema::create('auxiliar_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_curso');
            $tabla->string('estado');
        });
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_docente_invitador');
            $tabla->integer('id_docente_invitado');
            $tabla->integer('id_examen');
            $tabla->string('estado');
            $tabla->timestamps();
        });
        Schema::create('estudiante_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_estudiante_examen')->primary();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
        });
        Schema::create('estudiante_examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ee');
            $tabla->integer('id_ambiente');
        });
        Schema::create('registro_asistencia', function (Blueprint $tabla): void {
            $tabla->integer('id_ingreso')->primary();
            $tabla->integer('id_examen');
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_registrador')->nullable();
            $tabla->string('sis_estudiante')->nullable();
            $tabla->dateTime('fecha_registro')->nullable();
            $tabla->string('tipo_infraccion')->nullable();
        });
        Schema::create('configuracion_examen_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_configuracion_examen')->primary();
            $tabla->integer('id_examen');
            $tabla->integer('id_docente');
        });
        Schema::create('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_notificacion')->primary();
            $tabla->integer('id_central_riesgo')->nullable();
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_docente_destinatario')->nullable();
            $tabla->integer('id_invitacion')->nullable();
            $tabla->string('tipo');
            $tabla->string('estado');
            $tabla->boolean('oculta')->default(false);
            $tabla->timestamp('fecha_evento')->nullable();
            $tabla->timestamp('fecha_lectura')->nullable();
            $tabla->json('detalles')->nullable();
        });
    }

    private function crearDatos(): void
    {
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'],
            ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'cod_sis' => 'DOC1', 'password' => 'x', 'nombre_usuario' => 'Roberto', 'apellido' => 'Silva', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
            ['id_usuario' => self::INVITADO_ACEPTADO, 'cod_sis' => 'DOC2', 'password' => 'x', 'nombre_usuario' => 'Patricia', 'apellido' => 'Rojas', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
            ['id_usuario' => self::INVITADO_PENDIENTE, 'cod_sis' => 'DOC3', 'password' => 'x', 'nombre_usuario' => 'Fernando', 'apellido' => 'Vargas', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
            ['id_usuario' => self::AUX_ACTIVO, 'cod_sis' => 'AUX1', 'password' => 'x', 'nombre_usuario' => 'Diego', 'apellido' => 'Mendoza', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
            ['id_usuario' => self::AUX_BAJA, 'cod_sis' => 'AUX2', 'password' => 'x', 'nombre_usuario' => 'Sofia', 'apellido' => 'Castro', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
            ['id_usuario' => self::AUX_ELIMINADO, 'cod_sis' => 'AUX3', 'password' => 'x', 'nombre_usuario' => 'Lucas', 'apellido' => 'Paredes', 'estado_cuenta' => EstadoCuenta::Eliminada->value],
            ['id_usuario' => self::AUX_OTRO_CURSO, 'cod_sis' => 'AUX4', 'password' => 'x', 'nombre_usuario' => 'Elena', 'apellido' => 'Quispe', 'estado_cuenta' => EstadoCuenta::Habilitada->value],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::INVITADO_ACEPTADO, 'id_rol' => 1],
            ['id_usuario' => self::INVITADO_PENDIENTE, 'id_rol' => 1],
            ['id_usuario' => self::AUX_ACTIVO, 'id_rol' => 2],
            ['id_usuario' => self::AUX_BAJA, 'id_rol' => 2],
            ['id_usuario' => self::AUX_ELIMINADO, 'id_rol' => 2],
            ['id_usuario' => self::AUX_OTRO_CURSO, 'id_rol' => 2],
        ]);
        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => 1,
            'nombre_tipo_examen' => 'Examen primer parcial',
        ]);
        DB::table('curso')->insert([
            ['id_curso' => self::CURSO, 'nombre_curso' => 'Calculo I', 'sis_doc' => self::DOCENTE, 'estado' => 'EnCurso'],
            ['id_curso' => self::OTRO_CURSO, 'nombre_curso' => 'Fisica I', 'sis_doc' => self::INVITADO_ACEPTADO, 'estado' => 'EnCurso'],
        ]);
        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => '2026-12-10',
            'hora_inicio' => '08:00:00',
            'duracion' => 90,
            'creador' => self::DOCENTE,
            'tipo_examen' => 1,
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => self::EXAMEN, 'id_curso' => self::CURSO],
            ['id_examen' => self::EXAMEN, 'id_curso' => self::OTRO_CURSO],
        ]);
        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::AUX_ACTIVO, 'id_curso' => self::CURSO, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_BAJA, 'id_curso' => self::CURSO, 'estado' => 'Baja'],
            ['id_auxiliar' => self::AUX_ELIMINADO, 'id_curso' => self::CURSO, 'estado' => 'Activo'],
            ['id_auxiliar' => self::AUX_OTRO_CURSO, 'id_curso' => self::OTRO_CURSO, 'estado' => 'Activo'],
        ]);
        DB::table('invitacion_examen_compartido')->insert([
            ['id_invitacion' => 30, 'id_docente_invitador' => self::DOCENTE, 'id_docente_invitado' => self::INVITADO_ACEPTADO, 'id_examen' => self::EXAMEN, 'estado' => 'aceptado'],
            ['id_invitacion' => 31, 'id_docente_invitador' => self::DOCENTE, 'id_docente_invitado' => self::INVITADO_PENDIENTE, 'id_examen' => self::EXAMEN, 'estado' => 'pendiente'],
        ]);
    }
}
