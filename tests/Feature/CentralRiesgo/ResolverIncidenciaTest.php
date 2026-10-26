<?php

/**
 * @file    ResolverIncidenciaTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-10-09
 *
 * @updated 2026-10-09
 * @updated 2026-10-24
 * @updated 2026-10-26
 *
 * @description Pruebas HTTP y transaccionales con SQLite en memoria, sin usar Supabase.
 *
 * @changelog
 * - 2026-10-09  [Diego Tejerina]  test: cubrir revisión, permisos y duplicados (#142).
 * - 2026-10-24  [Valery D. Ortuno P]  test: confirmar deja el caso como
 *   `tramposo` (antes seguía como sospechoso).
 * - 2026-10-26  [Valery D. Ortuno P]  test: pendiente es sospechoso y
 *   confirmar lo pasa a tramposo, sin `estado_incidencia` ni `id_confirmador`
 *   (se prueba la migración que los elimina, también su reversión). El
 *   esquema lleva `id_curso`, que es lo que mira la policy: sin él todas las
 *   resoluciones daban 403.
 */

namespace Tests\Feature\CentralRiesgo;

use App\Enums\TipoInfraccion;
use App\Models\CentralRiesgo;
use App\Models\Usuario;
use App\Services\CentralRiesgo\ResolverIncidenciaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ResolverIncidenciaTest extends TestCase
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

        // Estado de Supabase antes del refactor: con `estado_incidencia` e
        // `id_confirmador`, que la migración nueva elimina.
        (require database_path('migrations/2026_10_09_000001_agregar_revision_incidencia.php'))->up();
        $this->migracionUnificar()->up();
    }

    protected function tearDown(): void
    {
        CentralRiesgo::flushEventListeners();
        DB::purge('incidencias_pruebas');
        parent::tearDown();
    }

    /** @throws \Throwable Si se altera el reportante o se duplica la incidencia. */
    public function test_confirma_pasando_a_tramposo_sin_duplicar_ni_cambiar_el_reportante(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web')
            ->postJson('/api/incidencias/1/confirmar', ['id_registrador' => 3])
            ->assertOk()->assertExactJson([
                'datos' => ['id_registro' => 1, 'tipo_infraccion' => 'tramposo'],
                'mensaje' => 'Incidencia confirmada correctamente.',
            ]);

        $this->assertDatabaseCount('central_riesgo', 2);
        // Confirmar pasa el caso a tramposo: el tipo es el estado
        // (tramposo = confirmado).
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => 1, 'id_registrador' => 2, 'tipo_infraccion' => 'tramposo',
        ]);
        $this->assertSame('Confirmado', CentralRiesgo::findOrFail(1)->tipo_infraccion->estado());
    }

    /** @throws \Throwable Si el descarte afecta otros registros o deja un riesgo. */
    public function test_rechaza_y_elimina_solo_la_incidencia_y_sus_notificaciones(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web')->postJson('/api/incidencias/1/rechazar')
            ->assertOk()->assertExactJson([
                'datos' => ['id_registro' => 1],
                'mensaje' => 'Incidencia rechazada correctamente.',
            ]);

        $this->assertDatabaseMissing('central_riesgo', ['id_registro' => 1]);
        $this->assertDatabaseMissing('notificacion_docente', ['id_central_riesgo' => 1]);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 2]);
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 2]);
        $this->assertDatabaseCount('usuario', 4);
        $this->assertDatabaseCount('examen', 2);
    }

    /** @throws \Throwable Si un invitado puede resolver una incidencia. */
    public function test_requiere_autenticacion_en_ambos_endpoints(): void
    {
        foreach (['confirmar', 'rechazar'] as $accion) {
            $this->postJson('/api/incidencias/1/'.$accion)->assertUnauthorized();
        }
        $this->assertPendiente();
    }

    /** @throws \Throwable Si un auxiliar o un usuario sin rol puede resolver. */
    public function test_rechaza_auxiliares_y_usuarios_sin_rol(): void
    {
        foreach ([2, 4] as $idUsuario) {
            foreach (['confirmar', 'rechazar'] as $accion) {
                $this->actingAs(Usuario::findOrFail($idUsuario), 'web')
                    ->postJson('/api/incidencias/1/'.$accion)->assertForbidden();
            }
        }
        $this->assertPendiente();
    }

    /** @throws \Throwable Si un docente de otro curso puede decidir sobre el caso. */
    public function test_rechaza_docente_de_otro_curso(): void
    {
        foreach (['confirmar', 'rechazar'] as $accion) {
            $this->actingAs(Usuario::findOrFail(3), 'web')
                ->postJson('/api/incidencias/1/'.$accion)->assertForbidden();
        }
        $this->assertPendiente();
    }

    /** @throws \Throwable Si compartir el examen basta para resolver una incidencia de otro curso. */
    public function test_solo_resuelve_el_docente_del_curso_de_la_incidencia(): void
    {
        DB::table('examen_curso')->insert(['id_examen' => 1, 'id_curso' => 2]);

        $this->actingAs(Usuario::findOrFail(3), 'web')->postJson('/api/incidencias/1/confirmar')
            ->assertForbidden();
        $this->assertPendiente();

        DB::table('central_riesgo')->where('id_registro', 1)->update(['id_curso' => 2]);
        $this->actingAs(Usuario::findOrFail(3), 'web')->postJson('/api/incidencias/1/confirmar')
            ->assertOk()->assertJsonPath('datos.tipo_infraccion', 'tramposo');
    }

    /** @throws \Throwable Si se resuelve nuevamente una incidencia confirmada. */
    public function test_no_permite_confirmar_ni_rechazar_una_incidencia_confirmada(): void
    {
        foreach (['confirmar', 'rechazar'] as $accion) {
            $this->actingAs(Usuario::findOrFail(1), 'web')->postJson('/api/incidencias/2/'.$accion)
                ->assertUnprocessable()->assertJsonStructure(['mensaje']);
        }
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 2, 'tipo_infraccion' => 'tramposo']);
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 2]);
    }

    /** @throws \Throwable Si un segundo envío crea registros o sobrescribe la decisión. */
    public function test_confirmacion_repetida_no_duplica_y_rechazo_posterior_falla(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web');
        $this->postJson('/api/incidencias/1/confirmar')->assertOk();
        $this->postJson('/api/incidencias/1/confirmar')->assertUnprocessable();
        $this->postJson('/api/incidencias/1/rechazar')->assertUnprocessable();
        $this->assertDatabaseCount('central_riesgo', 2);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 1, 'tipo_infraccion' => 'tramposo']);
    }

    /** @throws \Throwable Si un segundo descarte afecta otras incidencias. */
    public function test_rechazo_repetido_y_confirmacion_posterior_devuelven_404(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web');
        $this->postJson('/api/incidencias/1/rechazar')->assertOk();
        $this->postJson('/api/incidencias/1/rechazar')->assertNotFound();
        $this->postJson('/api/incidencias/1/confirmar')->assertNotFound();
        $this->assertDatabaseCount('central_riesgo', 1);
    }

    /** @throws \Throwable Si una incidencia inexistente o una ruta inválida es aceptada. */
    public function test_identificadores_inexistentes_o_invalidos_devuelven_404(): void
    {
        $this->actingAs(Usuario::findOrFail(1), 'web');
        foreach (['confirmar', 'rechazar'] as $accion) {
            $this->postJson('/api/incidencias/999/'.$accion)->assertNotFound();
            $this->postJson('/api/incidencias/incorrecto/'.$accion)->assertNotFound();
        }
        $this->assertPendiente();
    }

    /** @throws \Throwable Si un fallo deja borradas las notificaciones o el caso. */
    public function test_fallo_al_descartar_revierte_toda_la_transaccion(): void
    {
        CentralRiesgo::deleting(function (): void {
            throw new RuntimeException('Fallo simulado al eliminar la incidencia.');
        });

        try {
            app(ResolverIncidenciaService::class)->rechazar(1, Usuario::findOrFail(1));
            $this->fail('Debió fallar el descarte.');
        } catch (RuntimeException $error) {
            $this->assertSame('Fallo simulado al eliminar la incidencia.', $error->getMessage());
        }
        $this->assertPendiente();
        $this->assertDatabaseHas('notificacion_docente', ['id_central_riesgo' => 1]);
    }

    /** @throws \Throwable Si un request sin token supera la protección CSRF. */
    public function test_las_rutas_de_sesion_requieren_csrf(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $this->actingAs(Usuario::findOrFail(1), 'web');

        foreach (['confirmar', 'rechazar'] as $accion) {
            $this->postJson('/api/incidencias/1/'.$accion)->assertStatus(419);
        }
        $this->assertPendiente();
    }

    /** @throws \Throwable Si la migración pierde datos, no unifica o no es idempotente. */
    public function test_migracion_unifica_el_estado_en_el_tipo_y_se_puede_revertir(): void
    {
        $this->assertFalse(Schema::hasColumn('central_riesgo', 'estado_incidencia'));
        $this->assertFalse(Schema::hasColumn('central_riesgo', 'id_confirmador'));

        // Al revertir vuelven las columnas, con el estado calculado del tipo.
        $this->migracionUnificar()->down();
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 1, 'estado_incidencia' => 'Pendiente']);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 2, 'estado_incidencia' => 'Confirmado']);

        // Un sospechoso que un docente ya confirmó pasa a tramposo; un
        // tramposo que quedó Pendiente sigue tramposo (el tipo manda).
        DB::table('central_riesgo')->where('id_registro', 1)->update(['estado_incidencia' => 'Confirmado']);
        DB::table('central_riesgo')->where('id_registro', 2)->update(['estado_incidencia' => 'Pendiente']);
        $this->migracionUnificar()->up();
        $this->migracionUnificar()->up();

        $this->assertDatabaseCount('central_riesgo', 2);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 1, 'tipo_infraccion' => 'tramposo', 'id_registrador' => 2]);
        $this->assertDatabaseHas('central_riesgo', ['id_registro' => 2, 'tipo_infraccion' => 'tramposo']);
        $this->assertFalse(Schema::hasColumn('central_riesgo', 'estado_incidencia'));
    }

    /** Sigue sospechosa (pendiente) y con su reportante original. */
    private function assertPendiente(): void
    {
        $this->assertDatabaseHas('central_riesgo', [
            'id_registro' => 1, 'tipo_infraccion' => TipoInfraccion::Sospechoso->value, 'id_registrador' => 2,
        ]);
    }

    private function migracionUnificar(): object
    {
        return require database_path('migrations/2026_10_26_000001_unificar_estado_incidencia_en_tipo_infraccion.php');
    }

    private function crearEsquema(): void
    {
        Schema::create('usuario', function (Blueprint $tabla): void {
            $tabla->integer('id_usuario')->primary();
            $tabla->string('cod_sis');
            $tabla->string('password');
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
        Schema::create('curso', function (Blueprint $tabla): void {
            $tabla->integer('id_curso')->primary();
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
            $tabla->integer('id_registrador');
            $tabla->integer('id_examen');
            $tabla->integer('id_curso')->nullable();
            $tabla->string('tipo_infraccion');
            $tabla->foreign('id_registrador')->references('id_usuario')->on('usuario');
            $tabla->foreign('id_examen')->references('id_examen')->on('examen');
        });
        Schema::create('notificacion_docente', function (Blueprint $tabla): void {
            $tabla->integer('id_notificacion')->primary();
            $tabla->integer('id_central_riesgo');
            $tabla->foreign('id_central_riesgo')->references('id_registro')->on('central_riesgo');
        });
    }

    private function crearDatos(): void
    {
        foreach ([1, 2, 3, 4] as $idUsuario) {
            DB::table('usuario')->insert([
                'id_usuario' => $idUsuario, 'cod_sis' => 'TEST'.$idUsuario, 'password' => 'solo-pruebas',
            ]);
        }
        DB::table('rol')->insert([
            ['id_rol' => 1, 'nombre_rol' => 'docente'], ['id_rol' => 2, 'nombre_rol' => 'auxiliar'],
        ]);
        DB::table('rol_usuario')->insert([
            ['id_usuario' => 1, 'id_rol' => 1], ['id_usuario' => 2, 'id_rol' => 2],
            ['id_usuario' => 3, 'id_rol' => 1],
        ]);
        DB::table('curso')->insert([['id_curso' => 1, 'sis_doc' => 1], ['id_curso' => 2, 'sis_doc' => 3]]);
        DB::table('examen')->insert([['id_examen' => 1], ['id_examen' => 2]]);
        DB::table('examen_curso')->insert([
            ['id_examen' => 1, 'id_curso' => 1], ['id_examen' => 2, 'id_curso' => 2],
        ]);
        DB::table('central_riesgo')->insert([
            ['id_registro' => 1, 'id_registrador' => 2, 'id_examen' => 1, 'id_curso' => 1, 'tipo_infraccion' => 'sospechoso'],
            ['id_registro' => 2, 'id_registrador' => 2, 'id_examen' => 1, 'id_curso' => 1, 'tipo_infraccion' => 'tramposo'],
        ]);
        DB::table('notificacion_docente')->insert([
            ['id_notificacion' => 1, 'id_central_riesgo' => 1],
            ['id_notificacion' => 2, 'id_central_riesgo' => 2],
        ]);
    }
}
