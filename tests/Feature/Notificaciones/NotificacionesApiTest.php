<?php

/**
 * @file    NotificacionesApiTest.php
 *
 * @author  T1 - Tech One SRL
 *
 * @created 2026-10-11
 *
 * @updated 2026-10-26
 *
 * @description
 * Pruebas de integración de los endpoints de notificaciones: listado ordenado
 * de más reciente a más antiguo, lista vacía, auxiliar sin notificaciones,
 * ocultado lógico sin afectar el caso ni a otros usuarios, y la mezcla de los
 * dos tipos de aviso (HU 12 "Posible Tramposo" y HU 6 "Estudiante ingresado a
 * central de riesgo") que hoy devuelve el listado.
 *
 * Igual que otras pruebas del proyecto, el esquema se arma sobre SQLite en
 * memoria con una conexión dedicada, así NO se toca la base compartida de
 * Supabase. Se ejecuta además la migración de la columna `oculta` (T2) y la de
 * `tipo` (HU 6).
 *
 * @see  App\Services\Notificacion\NotificacionService
 * @see  App\Http\Controllers\Api\NotificacionController
 *
 * @changelog
 * - 2026-10-11  [T1]  test: criterios de HU 12 (T1/T2).
 * - 2026-10-11  [David E. Chavez T.]  test: criterios de HU 6 (T2): listado
 *   mezclando ambos tipos de aviso en orden inverso y ocultado de un aviso
 *   "central_riesgo" sin tocar el caso.
 * - 2026-10-11  [Valery D. Ortuno P]  test: el aviso "posible_tramposo" expone
 *   `referencia` (id_registro) y `detalle_href`, que son los que usa la tarjeta
 *   para abrir el detalle.
 * - 2026-10-26  [Valery D. Ortuno P]  test: sin `estado_incidencia` ni
 *   `id_confirmador`, eliminadas de la base (el tipo es el estado).
 */

namespace Tests\Feature\Notificaciones;

use App\Models\CentralRiesgo;
use App\Models\Usuario;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificacionesApiTest extends TestCase
{
    private const DOCENTE_A = 1;

    private const DOCENTE_B = 2;

    private const AUXILIAR = 3;

    private const DOCENTE_SIN_AVISOS = 4;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.notificaciones_pruebas' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('notificaciones_pruebas');

        $this->crearEsquema();

        (require database_path('migrations/2026_10_11_000001_agregar_oculta_a_notificacion_docente.php'))->up();
        (require database_path('migrations/2026_10_11_000002_agregar_tipo_a_notificacion_docente.php'))->up();

        $this->crearDatos();
    }

    protected function tearDown(): void
    {
        DB::purge('notificaciones_pruebas');
        parent::tearDown();
    }

    public function test_lista_las_notificaciones_del_docente_en_orden_descendente_y_sin_ocultas(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_A), 'web')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', 1)
            ->assertJsonPath('1.id', 2)
            ->assertJsonPath('0.titulo', 'Posible Tramposo')
            ->assertJsonPath('0.auxiliar', 'Aux Uno')
            ->assertJsonPath('0.estudiante', 'Juan Quispe')
            ->assertJsonPath('0.materia', 'Matemática I')
            // `referencia` es el id_registro de central_riesgo que abre el modal
            // de "Ver detalle" de la tarjeta; `detalle_href` es su respaldo sin JS.
            ->assertJsonPath('0.referencia', 1)
            ->assertJsonPath('0.detalle_href', route('incidencias.detalle', 1))
            ->assertJsonPath('0.hace', fn (string $valor): bool => str_starts_with($valor, 'Hace') && str_contains($valor, 'minuto'))
            ->assertJsonMissing(['id' => 3])
            ->assertJsonMissing(['id' => 4]);
    }

    public function test_docente_sin_notificaciones_recibe_lista_vacia(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_SIN_AVISOS), 'web')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_un_auxiliar_no_recibe_notificaciones(): void
    {
        $this->actingAs(Usuario::findOrFail(self::AUXILIAR), 'web')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_ocultar_una_notificacion_propia_la_persiste_y_no_cambia_el_caso(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_A), 'web')
            ->patchJson('/api/notificaciones/1/ocultar')
            ->assertOk();

        $this->assertDatabaseHas('notificacion_docente', ['id_notificacion' => 1, 'oculta' => true]);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 1, 'tipo_infraccion' => 'sospechoso']);

        $this->getJson('/api/notificaciones')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', 2);
    }

    public function test_no_puede_ocultar_una_notificacion_de_otro_docente(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_B), 'web')
            ->patchJson('/api/notificaciones/1/ocultar')
            ->assertForbidden();

        $this->assertDatabaseHas('notificacion_docente', ['id_notificacion' => 1, 'oculta' => false]);
    }

    public function test_ocultar_una_notificacion_inexistente_devuelve_404(): void
    {
        $this->actingAs(Usuario::findOrFail(self::DOCENTE_A), 'web')
            ->patchJson('/api/notificaciones/999/ocultar')
            ->assertNotFound();
    }

    public function test_ambos_endpoints_requieren_autenticacion(): void
    {
        $this->getJson('/api/notificaciones')->assertUnauthorized();
        $this->patchJson('/api/notificaciones/1/ocultar')->assertUnauthorized();
    }

    public function test_lista_mezcla_avisos_de_central_y_posible_tramposo_en_orden_descendente(): void
    {
        // Caso nuevo (ahora) con su aviso HU 6, más reciente que los del
        // fixture (-20 min, -2 h), para verificar el orden cronológico inverso.
        DB::table('central_riesgo')->insert([
            'id_registro' => 7, 'sis_estudiante' => '900000001', 'id_examen' => 1, 'id_curso' => 1,
            'id_registrador' => self::DOCENTE_A, 'tipo_infraccion' => 'tramposo',
            'fecha_registro' => Carbon::now()->subMinutes(3)->toDateTimeString(),
        ]);
        DB::table('notificacion_docente')->insert([
            'id_notificacion' => 5,
            'id_central_riesgo' => 7,
            'id_curso' => 1,
            'tipo' => 'central_riesgo',
            'estado' => 'recibido',
            'oculta' => false,
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE_A), 'web')
            ->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonCount(3)
            // El más reciente primero: el aviso de central de riesgos.
            ->assertJsonPath('0.id', 5)
            ->assertJsonPath('0.tipo', 'central_riesgo')
            ->assertJsonPath('0.titulo', 'Estudiante ingresado a central de riesgo')
            ->assertJsonPath('0.estudiante', 'Juan Quispe')
            ->assertJsonPath('0.materia', 'Matemática I')
            ->assertJsonPath('0.descripcion', 'El estudiante Juan Quispe fue ingresado a la central de riesgo en la materia de Matemática I.')
            ->assertJsonPath('0.hace', fn (string $valor): bool => str_starts_with($valor, 'Hace') && str_contains($valor, 'minuto'))
            ->assertJsonPath('0.detalle_href', '#')
            // Detrás, los avisos "Posible Tramposo" del mismo docente.
            ->assertJsonPath('1.id', 1)
            ->assertJsonPath('1.tipo', 'posible_tramposo')
            ->assertJsonPath('1.titulo', 'Posible Tramposo')
            ->assertJsonPath('2.id', 2);
    }

    public function test_ocultar_un_aviso_de_central_de_riesgos_no_toca_el_caso(): void
    {
        DB::table('notificacion_docente')->insert([
            'id_notificacion' => 5,
            'id_central_riesgo' => 1,
            'id_curso' => 1,
            'tipo' => 'central_riesgo',
            'estado' => 'recibido',
            'oculta' => false,
        ]);

        $this->actingAs(Usuario::findOrFail(self::DOCENTE_A), 'web')
            ->patchJson('/api/notificaciones/5/ocultar')
            ->assertOk();

        $this->assertDatabaseHas('notificacion_docente', ['id_notificacion' => 5, 'oculta' => true]);
        // El caso de la central de riesgos queda intacto.
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => 1,
            'tipo_infraccion' => 'sospechoso',
        ]);

        $this->getJson('/api/notificaciones')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonMissing(['id' => 5]);
    }

    public function test_registrar_un_sospechoso_genera_aviso_y_un_tramposo_no(): void
    {
        DB::table('central_riesgo')->insert([
            'id_registro' => 5, 'sis_estudiante' => '900000001', 'id_examen' => 1, 'id_curso' => 1,
            'id_registrador' => self::AUXILIAR, 'tipo_infraccion' => 'sospechoso', 'fecha_registro' => now()->toDateTimeString(),
        ]);
        $sospechoso = CentralRiesgo::query()->findOrFail(5);

        $aviso = app(NotificacionService::class)->notificarPosibleTramposo($sospechoso);

        $this->assertNotNull($aviso);
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 5, 'id_curso' => 1]);
        $this->assertFalse((bool) $aviso->oculta);

        DB::table('central_riesgo')->insert([
            'id_registro' => 6, 'sis_estudiante' => '900000001', 'id_examen' => 1, 'id_curso' => 1,
            'id_registrador' => self::DOCENTE_A, 'tipo_infraccion' => 'tramposo',
            'fecha_registro' => now()->toDateTimeString(),
        ]);

        $this->assertNull(
            app(NotificacionService::class)->notificarPosibleTramposo(CentralRiesgo::query()->findOrFail(6))
        );
        $this->assertDatabaseMissing('notificacion_docente', ['id_central_riesgo' => 6]);
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('correo')->nullable();
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
        Schema::create('invitacion_examen_compartido', function (Blueprint $tabla): void {
            $tabla->integer('id_invitacion')->primary();
            $tabla->integer('id_docente_invitador');
            $tabla->integer('id_docente_invitado');
            $tabla->integer('id_examen');
            $tabla->string('estado');
            $tabla->timestamps();
        });
        Schema::create('estudiante', function (Blueprint $tabla): void {
            $tabla->string('sis_estudiante')->primary();
            $tabla->string('nombre_estudiante');
            $tabla->string('apellido_estudiante');
            $tabla->string('carrera')->nullable();
        });
        Schema::create('central_riesgo', function (Blueprint $tabla): void {
            $tabla->integer('id_registro')->primary();
            $tabla->string('sis_estudiante');
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_registrador');
            $tabla->string('motivo')->nullable();
            $tabla->string('detalle_motivo')->nullable();
            $tabla->dateTime('fecha_registro')->nullable();
            $tabla->string('tipo_infraccion');
        });
        Schema::create('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_notificacion')->primary();
            $tabla->integer('id_central_riesgo')->nullable();
            $tabla->integer('id_curso')->nullable();
            $tabla->integer('id_docente_destinatario')->nullable();
            $tabla->integer('id_invitacion')->nullable();
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
            ['id_usuario' => self::DOCENTE_SIN_AVISOS, 'cod_sis' => 'DOCC', 'password' => 'x', 'nombre_usuario' => 'Doc', 'apellido' => 'C'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_A, 'id_rol' => 1],
            ['id_usuario' => self::DOCENTE_B, 'id_rol' => 1],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => 2],
            ['id_usuario' => self::DOCENTE_SIN_AVISOS, 'id_rol' => 1],
        ]);
        DB::table('tipo_examen')->insert(['id_tipo_examen' => 900001, 'nombre_tipo_examen' => 'PP']);
        DB::table('curso')->insert([
            ['id_curso' => 1, 'nombre_curso' => 'Matemática I', 'sis_doc' => self::DOCENTE_A],
            ['id_curso' => 2, 'nombre_curso' => 'Física II', 'sis_doc' => self::DOCENTE_B],
            ['id_curso' => 3, 'nombre_curso' => 'Historia', 'sis_doc' => self::DOCENTE_SIN_AVISOS],
        ]);
        DB::table('examen')->insert([
            ['id_examen' => 1, 'tipo_examen' => 900001],
            ['id_examen' => 2, 'tipo_examen' => 900001],
        ]);
        DB::table('examen_curso')->insert([
            ['id_examen' => 1, 'id_curso' => 1],
            ['id_examen' => 2, 'id_curso' => 2],
        ]);
        DB::table('estudiante')->insert([
            ['sis_estudiante' => '900000001', 'nombre_estudiante' => 'Juan', 'apellido_estudiante' => 'Quispe'],
            ['sis_estudiante' => '900000002', 'nombre_estudiante' => 'Ana', 'apellido_estudiante' => 'Rojas'],
            ['sis_estudiante' => '900000003', 'nombre_estudiante' => 'Luis', 'apellido_estudiante' => 'Vega'],
            ['sis_estudiante' => '900000004', 'nombre_estudiante' => 'Eva', 'apellido_estudiante' => 'Soto'],
        ]);

        $ahora = Carbon::now();
        DB::table('central_riesgo')->insert([
            ['id_registro' => 1, 'sis_estudiante' => '900000001', 'id_examen' => 1, 'id_curso' => 1, 'id_registrador' => self::AUXILIAR, 'tipo_infraccion' => 'sospechoso', 'fecha_registro' => $ahora->copy()->subMinutes(20)->toDateTimeString()],
            ['id_registro' => 2, 'sis_estudiante' => '900000002', 'id_examen' => 1, 'id_curso' => 1, 'id_registrador' => self::AUXILIAR, 'tipo_infraccion' => 'sospechoso', 'fecha_registro' => $ahora->copy()->subHours(2)->toDateTimeString()],
            ['id_registro' => 3, 'sis_estudiante' => '900000003', 'id_examen' => 2, 'id_curso' => 2, 'id_registrador' => self::AUXILIAR, 'tipo_infraccion' => 'sospechoso', 'fecha_registro' => $ahora->copy()->subHours(3)->toDateTimeString()],
            ['id_registro' => 4, 'sis_estudiante' => '900000004', 'id_examen' => 1, 'id_curso' => 1, 'id_registrador' => self::AUXILIAR, 'tipo_infraccion' => 'sospechoso', 'fecha_registro' => $ahora->copy()->subHours(4)->toDateTimeString()],
        ]);

        DB::table('notificacion_docente')->insert([
            ['id_notificacion' => 1, 'id_central_riesgo' => 1, 'id_curso' => 1, 'estado' => 'recibido', 'oculta' => false],
            ['id_notificacion' => 2, 'id_central_riesgo' => 2, 'id_curso' => 1, 'estado' => 'recibido', 'oculta' => false],
            ['id_notificacion' => 3, 'id_central_riesgo' => 3, 'id_curso' => 2, 'estado' => 'recibido', 'oculta' => false],
            ['id_notificacion' => 4, 'id_central_riesgo' => 4, 'id_curso' => 1, 'estado' => 'recibido', 'oculta' => true],
        ]);
    }
}
