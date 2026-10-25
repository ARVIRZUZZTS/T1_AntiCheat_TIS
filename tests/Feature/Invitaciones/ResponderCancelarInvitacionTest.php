<?php

namespace Tests\Feature\Invitaciones;

use App\Models\Usuario;
use App\Services\Invitacion\ResponderInvitacionExamenService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Respuesta (aceptar/rechazar) y cancelación de invitaciones de examen
 * compartido (#223). Mismo harness que InvitacionesExamenTest: DatabaseTransactions
 * contra el esquema real, con IDs dedicados (992xxx) que se revierten al terminar.
 */
class ResponderCancelarInvitacionTest extends TestCase
{
    use DatabaseTransactions;

    private const INVITADOR = 992001;

    private const INVITADO = 992002;

    private const OTRO_DOCENTE = 992003;

    private const AUXILIAR = 992004;

    private const ROL_DOCENTE = 992001;

    private const ROL_AUXILIAR = 992002;

    private const TIPO_EXAMEN = 992001;

    private const CURSO = 992001;

    private const EXAMEN = 992001;

    private const INVITACION = 992001;

    private const NOTIFICACION = 992001;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('rol')->insert([
            ['id_rol' => self::ROL_DOCENTE, 'nombre_rol' => 'docente'],
            ['id_rol' => self::ROL_AUXILIAR, 'nombre_rol' => 'auxiliar'],
        ]);

        DB::table('usuario')->insert([
            ['id_usuario' => self::INVITADOR, 'cod_sis' => 'DOC992001', 'correo' => 'inv.ador@example.com', 'password' => 'x', 'nombre_usuario' => 'Carlos', 'apellido' => 'Rojas'],
            ['id_usuario' => self::INVITADO, 'cod_sis' => 'DOC992002', 'correo' => 'inv.ado@example.com', 'password' => 'x', 'nombre_usuario' => 'Ana', 'apellido' => 'Pérez'],
            ['id_usuario' => self::OTRO_DOCENTE, 'cod_sis' => 'DOC992003', 'correo' => 'otro@example.com', 'password' => 'x', 'nombre_usuario' => 'Beatriz', 'apellido' => 'Mamani'],
            ['id_usuario' => self::AUXILIAR, 'cod_sis' => 'AUX992004', 'correo' => 'aux@example.com', 'password' => 'x', 'nombre_usuario' => 'Luis', 'apellido' => 'Auxiliar'],
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::INVITADOR, 'id_rol' => self::ROL_DOCENTE],
            ['id_usuario' => self::INVITADO, 'id_rol' => self::ROL_DOCENTE],
            ['id_usuario' => self::OTRO_DOCENTE, 'id_rol' => self::ROL_DOCENTE],
            ['id_usuario' => self::AUXILIAR, 'id_rol' => self::ROL_AUXILIAR],
        ]);

        DB::table('tipo_examen')->insert([
            'id_tipo_examen' => self::TIPO_EXAMEN,
            'nombre_tipo_examen' => 'examen parcial',
        ]);

        DB::table('curso')->insert([
            'id_curso' => self::CURSO,
            'nombre_curso' => 'Cálculo I',
            'sis_doc' => self::INVITADOR,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => '2026-11-10',
            'hora_inicio' => '08:00',
            'duracion' => 90,
            'creador' => self::INVITADOR,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);

        DB::table('examen_curso')->insert([
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
        ]);
    }

    public function test_el_invitado_acepta_y_se_notifica_al_invitador(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADO), 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertOk()
            ->assertJsonPath('data.estado', 'aceptado');

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'aceptado',
        ]);
        $this->assertDatabaseHas('configuracion_examen_docente', [
            'id_examen' => self::EXAMEN,
            'id_docente' => self::INVITADO,
        ]);

        // El aviso se reutiliza: ahora apunta al invitador y cambia de tipo, sin
        // crear una segunda fila (índice único por id_invitacion).
        $this->assertDatabaseHas('notificacion_docente', [
            'id_invitacion' => self::INVITACION,
            'id_docente_destinatario' => self::INVITADOR,
            'tipo' => NotificacionService::TIPO_RESPUESTA_INVITACION,
            'oculta' => false,
        ]);
        $this->assertSame(1, DB::table('notificacion_docente')->where('id_invitacion', self::INVITACION)->count());

        // El invitador ve la respuesta en su bandeja.
        $this->actingAs(Usuario::query()->findOrFail(self::INVITADOR), 'web')
            ->getJson(route('api.notificaciones.index'))
            ->assertOk()
            ->assertJsonPath('0.tipo', NotificacionService::TIPO_RESPUESTA_INVITACION)
            ->assertJsonPath('0.datos.respuesta', 'aceptado');
    }

    public function test_el_invitado_rechaza_y_se_notifica_al_invitador(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADO), 'web')
            ->postJson(route('api.invitaciones.rechazar', self::INVITACION))
            ->assertOk()
            ->assertJsonPath('data.estado', 'rechazado');

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'rechazado',
        ]);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_invitacion' => self::INVITACION,
            'id_docente_destinatario' => self::INVITADOR,
            'tipo' => NotificacionService::TIPO_RESPUESTA_INVITACION,
        ]);
    }

    public function test_el_invitador_cancela_una_pendiente_y_se_oculta_el_aviso_del_invitado(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADOR), 'web')
            ->postJson(route('api.invitaciones.cancelar', self::INVITACION))
            ->assertOk()
            ->assertJsonPath('data.estado', 'cancelado');

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'cancelado',
        ]);
        // El aviso del invitado queda oculto: ya no puede responder una cancelada.
        $this->assertDatabaseHas('notificacion_docente', [
            'id_invitacion' => self::INVITACION,
            'oculta' => true,
        ]);
    }

    public function test_no_se_puede_responder_una_invitacion_cancelada(): void
    {
        $this->crearInvitacion('cancelado');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADO), 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertUnprocessable()
            ->assertJsonPath('errors.estado.0', ResponderInvitacionExamenService::MENSAJE_NO_PENDIENTE);

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'cancelado',
        ]);
    }

    public function test_aceptar_dos_veces_no_cambia_el_estado_la_segunda(): void
    {
        $this->crearInvitacion('pendiente');

        $invitado = Usuario::query()->findOrFail(self::INVITADO);

        $this->actingAs($invitado, 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertOk();

        $this->actingAs($invitado, 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertUnprocessable()
            ->assertJsonPath('errors.estado.0', ResponderInvitacionExamenService::MENSAJE_NO_PENDIENTE);
    }

    public function test_otro_docente_no_puede_aceptar_una_invitacion_ajena(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::OTRO_DOCENTE), 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertForbidden();

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'pendiente',
        ]);
    }

    public function test_el_invitado_no_puede_cancelar_la_invitacion(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADO), 'web')
            ->postJson(route('api.invitaciones.cancelar', self::INVITACION))
            ->assertForbidden();

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_invitacion' => self::INVITACION,
            'estado' => 'pendiente',
        ]);
    }

    public function test_el_invitador_no_puede_aceptar_su_propia_invitacion(): void
    {
        $this->crearInvitacion('pendiente');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADOR), 'web')
            ->postJson(route('api.invitaciones.aceptar', self::INVITACION))
            ->assertForbidden();
    }

    public function test_el_docente_que_acepto_puede_invitar_a_otro(): void
    {
        $this->crearInvitacion('aceptado');

        $this->actingAs(Usuario::query()->findOrFail(self::INVITADO), 'web')
            ->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
                'docente_invitado_id' => self::OTRO_DOCENTE,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_examen' => self::EXAMEN,
            'id_docente_invitador' => self::INVITADO,
            'id_docente_invitado' => self::OTRO_DOCENTE,
            'estado' => 'pendiente',
        ]);
    }

    /**
     * Crea una invitación en el estado dado y su aviso (como lo habría dejado el
     * envío), para probar también que la respuesta/cancelación reutiliza u oculta
     * ese aviso.
     */
    private function crearInvitacion(string $estado): void
    {
        DB::table('invitacion_examen_compartido')->insert([
            'id_invitacion' => self::INVITACION,
            'id_docente_invitador' => self::INVITADOR,
            'id_docente_invitado' => self::INVITADO,
            'id_examen' => self::EXAMEN,
            'estado' => $estado,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notificacion_docente')->insert([
            'id_notificacion' => self::NOTIFICACION,
            'id_central_riesgo' => null,
            'id_curso' => null,
            'id_docente_destinatario' => self::INVITADO,
            'id_invitacion' => self::INVITACION,
            'tipo' => NotificacionService::TIPO_INVITACION_EXAMEN,
            'estado' => NotificacionService::ESTADO_RECIBIDO,
            'oculta' => false,
        ]);
    }
}
