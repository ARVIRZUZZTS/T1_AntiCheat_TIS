<?php

/**
 * @file    CambiosExamenNotificacionTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-11-24
 *
 * @description
 * Pruebas de los avisos que se generan al editar un examen. Verifican que solo
 * se notifiquen los cambios de ambientes, fecha u hora, que los auxiliares
 * asignados (por curso o directamente al examen) reciban el aviso, que un examen
 * compartido también avise a los docentes involucrados y que el propio editor y
 * los usuarios ajenos queden fuera.
 *
 * Igual que otras pruebas de notificaciones, el esquema se arma sobre SQLite en
 * memoria con una conexión dedicada: no se toca la base compartida.
 *
 * @see  App\Services\Examen\NotificarCambiosExamenService
 * @see  App\Services\Notificacion\NotificacionService
 *
 * @changelog
 * - 2026-11-24  [Equipo T1]  test: avisos de cambios de ambientes, fecha y hora
 *   para auxiliares y docentes de exámenes compartidos.
 * - 2026-11-24  [Equipo T1]  test: el guardado de la edición llama al aviso con
 *   el docente que hace el cambio.
 */

namespace Tests\Feature\Notificaciones;

use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Examen\NotificarCambiosExamenService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CambiosExamenNotificacionTest extends TestCase
{
    private const EDITOR = 1;

    private const INVITADO = 2;

    private const AUX_CURSO = 3;

    private const AUX_EXAMEN = 4;

    private const OTRO_DOCENTE = 5;

    private const OTRO_AUX = 6;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-24 10:00:00');

        config(['database.connections.cambios_examen_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('cambios_examen_pruebas');

        $this->crearEsquema();
        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('cambios_examen_pruebas');
        parent::tearDown();
    }

    public function test_un_examen_compartido_avisa_a_auxiliares_y_docentes_involucrados(): void
    {
        $servicio = app(NotificarCambiosExamenService::class);
        $examen = Examen::query()->findOrFail(10);
        $antes = $servicio->capturar($examen);

        DB::table('examen')->where('id_examen', 10)->update([
            'fecha' => '2026-12-05',
            'hora_inicio' => '09:30:00',
        ]);
        DB::table('examen_ambiente')->where('id_examen', 10)->delete();
        DB::table('examen_ambiente')->insert(['id_examen' => 10, 'id_ambiente' => 102]);

        $avisos = $servicio->notificar($examen->refresh(), $antes, Usuario::query()->findOrFail(self::EDITOR));

        $this->assertCount(4, $avisos);
        $this->assertDatabaseCount('notificacion_docente', 4);

        $destinatarios = DB::table('notificacion_docente')
            ->pluck('id_docente_destinatario')
            ->map(fn (int $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $this->assertSame([self::INVITADO, self::AUX_CURSO, self::AUX_EXAMEN, self::OTRO_DOCENTE], $destinatarios);

        foreach ($destinatarios as $id) {
            $this->assertDatabaseHas('notificacion_docente', [
                'id_docente_destinatario' => $id,
                'tipo' => NotificacionService::TIPO_CAMBIO_EXAMEN,
                'estado' => NotificacionService::ESTADO_RECIBIDO,
                'oculta' => false,
                'fecha_evento' => '2026-11-24 10:00:00',
            ]);
        }

        $detalles = json_decode((string) DB::table('notificacion_docente')
            ->where('id_docente_destinatario', self::AUX_CURSO)
            ->value('detalles'), true);

        $this->assertSame(10, $detalles['examen_id']);
        $this->assertTrue($detalles['compartido']);
        $this->assertSame([
            'fecha 01/12/2026 → 05/12/2026',
            'hora de inicio 08:00 → 09:30',
            'ambientes Aula 625 → Laboratorio 3',
        ], $detalles['cambios']);

        $aviso = app(NotificacionService::class)
            ->listarPara(Usuario::query()->findOrFail(self::AUX_CURSO))
            ->first();

        $this->assertSame(NotificacionService::TIPO_CAMBIO_EXAMEN, $aviso['tipo']);
        $this->assertSame(NotificacionService::TITULO_CAMBIO_EXAMEN, $aviso['titulo']);
        $this->assertStringContainsString('El examen de Examen final', $aviso['descripcion']);
        $this->assertStringContainsString(
            'fecha 01/12/2026 → 05/12/2026; hora de inicio 08:00 → 09:30; ambientes Aula 625 → Laboratorio 3.',
            $aviso['descripcion'],
        );
        $this->assertSame('2026-11-24 10:00:00', $aviso['fecha_registro']);
        $this->assertNotSame('', $aviso['hace']);
    }

    public function test_sin_cambios_de_ambientes_fecha_ni_hora_no_notifica(): void
    {
        $servicio = app(NotificarCambiosExamenService::class);
        $examen = Examen::query()->findOrFail(10);
        $antes = $servicio->capturar($examen);

        $avisos = $servicio->notificar($examen, $antes, Usuario::query()->findOrFail(self::EDITOR));

        $this->assertSame([], $avisos);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    public function test_un_examen_no_compartido_solo_avisa_a_los_auxiliares_asignados(): void
    {
        $servicio = app(NotificarCambiosExamenService::class);
        $examen = Examen::query()->findOrFail(11);
        $antes = $servicio->capturar($examen);

        DB::table('examen')->where('id_examen', 11)->update(['hora_inicio' => '15:00:00']);

        $avisos = $servicio->notificar($examen->refresh(), $antes, Usuario::query()->findOrFail(self::EDITOR));

        $this->assertCount(1, $avisos);
        $this->assertDatabaseCount('notificacion_docente', 1);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_docente_destinatario' => self::AUX_CURSO,
            'tipo' => NotificacionService::TIPO_CAMBIO_EXAMEN,
        ]);
        $this->assertDatabaseMissing('notificacion_docente', [
            'id_docente_destinatario' => self::INVITADO,
        ]);
        $this->assertDatabaseMissing('notificacion_docente', [
            'id_docente_destinatario' => self::OTRO_DOCENTE,
        ]);

        $detalles = json_decode((string) DB::table('notificacion_docente')->value('detalles'), true);
        $this->assertFalse($detalles['compartido']);
        $this->assertSame(['hora de inicio 14:00 → 15:00'], $detalles['cambios']);
    }

    public function test_la_edicion_de_examen_esta_conectada_con_el_aviso_de_cambios(): void
    {
        $servicio = (string) file_get_contents(app_path('Services/Examen/ActualizarExamenService.php'));
        $controlador = (string) file_get_contents(app_path('Http/Controllers/Api/ExamenEdicionController.php'));

        $this->assertStringContainsString('$this->cambios->capturar($examen)', $servicio);
        $this->assertStringContainsString('$this->cambios->notificar($examen, $antes, $editor)', $servicio);
        $this->assertStringContainsString('$actualizar->ejecutar($examen, $request->validated(), $editor)', $controlador);
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
        Schema::create('tipo_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_tipo_examen')->primary();
            $tabla->string('nombre_tipo_examen');
        });
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
            $tabla->string('nombre_curso');
            $tabla->integer('sis_doc');
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
        Schema::create('ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_ambiente')->primary();
            $tabla->string('nombre_ambiente');
        });
        Schema::create('examen_ambiente', function (Blueprint $tabla): void {
            $tabla->integer('id_examen');
            $tabla->integer('id_ambiente');
        });
        Schema::create('auxiliar_curso', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_curso');
            $tabla->string('estado');
        });
        Schema::create('auxiliar_examen', function (Blueprint $tabla): void {
            $tabla->integer('id_auxiliar');
            $tabla->integer('id_examen');
        });
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_docente_invitador');
            $tabla->integer('id_docente_invitado');
            $tabla->integer('id_examen');
            $tabla->string('estado');
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
            ['id_usuario' => self::EDITOR, 'cod_sis' => 'DOC1', 'password' => 'x', 'nombre_usuario' => 'Ana', 'apellido' => 'Editor'],
            ['id_usuario' => self::INVITADO, 'cod_sis' => 'DOC2', 'password' => 'x', 'nombre_usuario' => 'Beto', 'apellido' => 'Invitado'],
            ['id_usuario' => self::AUX_CURSO, 'cod_sis' => 'AUX1', 'password' => 'x', 'nombre_usuario' => 'Cara', 'apellido' => 'Curso'],
            ['id_usuario' => self::AUX_EXAMEN, 'cod_sis' => 'AUX2', 'password' => 'x', 'nombre_usuario' => 'Dani', 'apellido' => 'Examen'],
            ['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => 'DOC3', 'password' => 'x', 'nombre_usuario' => 'Eva', 'apellido' => 'Dueña'],
            ['id_usuario' => self::OTRO_AUX, 'cod_sis' => 'AUX3', 'password' => 'x', 'nombre_usuario' => 'Fito', 'apellido' => 'Ajeno'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::EDITOR, 'id_rol' => 1],
            ['id_usuario' => self::INVITADO, 'id_rol' => 1],
            ['id_usuario' => self::OTRO_DOCENTE, 'id_rol' => 1],
            ['id_usuario' => self::AUX_CURSO, 'id_rol' => 2],
            ['id_usuario' => self::AUX_EXAMEN, 'id_rol' => 2],
            ['id_usuario' => self::OTRO_AUX, 'id_rol' => 2],
        ]);
        DB::table('tipo_examen')->insert(['id_tipo_examen' => 900010, 'nombre_tipo_examen' => 'Examen final']);
        DB::table('curso')->insert([
            ['id_curso' => 20, 'nombre_curso' => 'Matemática I', 'sis_doc' => self::EDITOR],
            ['id_curso' => 21, 'nombre_curso' => 'Física II', 'sis_doc' => self::OTRO_DOCENTE],
            ['id_curso' => 22, 'nombre_curso' => 'Historia', 'sis_doc' => self::EDITOR],
        ]);
        DB::table('examen')->insert([
            ['id_examen' => 10, 'fecha' => '2026-12-01', 'hora_inicio' => '08:00:00', 'duracion' => 60, 'creador' => self::EDITOR, 'tipo_examen' => 900010],
            ['id_examen' => 11, 'fecha' => '2026-12-10', 'hora_inicio' => '14:00:00', 'duracion' => 60, 'creador' => self::EDITOR, 'tipo_examen' => 900010],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => 10, 'id_curso' => 20],
            ['id_examen' => 10, 'id_curso' => 21],
            ['id_examen' => 11, 'id_curso' => 20],
        ]);
        DB::table('ambiente')->insert([
            ['id_ambiente' => 101, 'nombre_ambiente' => 'Aula 625'],
            ['id_ambiente' => 102, 'nombre_ambiente' => 'Laboratorio 3'],
        ]);
        DB::table('examen_ambiente')->insert([
            ['id_examen' => 10, 'id_ambiente' => 101],
            ['id_examen' => 11, 'id_ambiente' => 101],
        ]);
        DB::table('auxiliar_curso')->insert([
            ['id_auxiliar' => self::AUX_CURSO, 'id_curso' => 20, 'estado' => 'Activo'],
            ['id_auxiliar' => self::OTRO_AUX, 'id_curso' => 22, 'estado' => 'Activo'],
        ]);
        DB::table('auxiliar_examen')->insert([
            ['id_auxiliar' => self::AUX_EXAMEN, 'id_examen' => 10],
        ]);
        DB::table('invitacion_examen_compartido')->insert([
            ['id_invitacion' => 30, 'id_docente_invitador' => self::EDITOR, 'id_docente_invitado' => self::INVITADO, 'id_examen' => 10, 'estado' => 'aceptado'],
        ]);
    }
}
