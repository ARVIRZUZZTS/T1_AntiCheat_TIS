<?php

namespace Tests\Feature\Invitaciones;

use App\Models\Examen;
use App\Models\Usuario;
use App\Services\Invitacion\EnviarInvitacionExamenService;
use App\Services\Notificacion\NotificacionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InvitacionesExamenTest extends TestCase
{
    use DatabaseTransactions;

    private const DOCENTE_CREADOR = 991001;

    private const DOCENTE_INVITADO = 991002;

    private const OTRO_DOCENTE = 991003;

    private const AUXILIAR = 991004;

    private const EXAMEN = 991001;

    private const CURSO = 991001;

    private const ROL_DOCENTE = 991001;

    private const ROL_AUXILIAR = 991002;

    private const TIPO_EXAMEN = 991001;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('rol')->insert([
            ['id_rol' => self::ROL_DOCENTE, 'nombre_rol' => 'docente'],
            ['id_rol' => self::ROL_AUXILIAR, 'nombre_rol' => 'auxiliar'],
        ]);

        DB::table('usuario')->insert([
            [
                'id_usuario' => self::DOCENTE_CREADOR,
                'cod_sis' => 'DOC991001',
                'correo' => 'carlos@example.com',
                'password' => 'x',
                'nombre_usuario' => 'Carlos',
                'apellido' => 'Rojas',
            ],
            [
                'id_usuario' => self::DOCENTE_INVITADO,
                'cod_sis' => 'DOC991002',
                'correo' => 'ana.perez@example.com',
                'password' => 'x',
                'nombre_usuario' => 'Ana',
                'apellido' => 'Pérez',
            ],
            [
                'id_usuario' => self::OTRO_DOCENTE,
                'cod_sis' => 'DOC991003',
                'correo' => 'beatriz@example.com',
                'password' => 'x',
                'nombre_usuario' => 'Beatriz',
                'apellido' => 'Mamani',
            ],
            [
                'id_usuario' => self::AUXILIAR,
                'cod_sis' => 'AUX991004',
                'correo' => 'ana.auxiliar@example.com',
                'password' => 'x',
                'nombre_usuario' => 'Ana',
                'apellido' => 'Auxiliar',
            ],
        ]);

        DB::table('rol_usuario')->insert([
            ['id_usuario' => self::DOCENTE_CREADOR, 'id_rol' => self::ROL_DOCENTE],
            ['id_usuario' => self::DOCENTE_INVITADO, 'id_rol' => self::ROL_DOCENTE],
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
            'sis_doc' => self::DOCENTE_CREADOR,
            'fecha_creacion' => now()->toDateString(),
            'estado' => 'EnCurso',
        ]);

        DB::table('examen')->insert([
            'id_examen' => self::EXAMEN,
            'fecha' => '2026-11-10',
            'hora_inicio' => '08:00',
            'duracion' => 90,
            'creador' => self::DOCENTE_CREADOR,
            'tipo_examen' => self::TIPO_EXAMEN,
        ]);

        DB::table('examen_curso')->insert([
            'id_examen' => self::EXAMEN,
            'id_curso' => self::CURSO,
        ]);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE_CREADOR), 'web');
    }

    public function test_busca_docentes_por_nombre_o_correo_y_excluye_otros_roles(): void
    {
        $this->getJson(route('api.examenes.docentes-invitables', [self::EXAMEN, 'busqueda' => 'Ana']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', self::DOCENTE_INVITADO)
            ->assertJsonPath('data.0.nombre', 'Ana Pérez');

        $this->getJson(route('api.examenes.docentes-invitables', [self::EXAMEN, 'busqueda' => 'perez@example']))
            ->assertOk()
            ->assertJsonPath('data.0.correo', 'ana.perez@example.com');
    }

    public function test_rechaza_caracteres_invalidos_en_la_busqueda(): void
    {
        $this->getJson(route('api.examenes.docentes-invitables', [self::EXAMEN, 'busqueda' => 'Ana<script>']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('busqueda');
    }

    public function test_envia_invitacion_pendiente_y_notifica_al_docente_correcto(): void
    {
        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::DOCENTE_INVITADO,
        ])
            ->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente')
            ->assertJsonPath('data.invitador.id', self::DOCENTE_CREADOR)
            ->assertJsonPath('data.invitado.id', self::DOCENTE_INVITADO)
            ->assertJsonPath('data.examen.materias.0', 'Cálculo I');

        $this->assertDatabaseHas('invitacion_examen_compartido', [
            'id_examen' => self::EXAMEN,
            'id_docente_invitador' => self::DOCENTE_CREADOR,
            'id_docente_invitado' => self::DOCENTE_INVITADO,
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseHas('notificacion_docente', [
            'id_docente_destinatario' => self::DOCENTE_INVITADO,
            'tipo' => NotificacionService::TIPO_INVITACION_EXAMEN,
        ]);

        $this->actingAs(Usuario::query()->findOrFail(self::DOCENTE_INVITADO), 'web')
            ->getJson(route('api.notificaciones.index'))
            ->assertOk()
            ->assertJsonPath('0.tipo', NotificacionService::TIPO_INVITACION_EXAMEN)
            ->assertJsonPath('0.estado', 'pendiente')
            ->assertJsonPath('0.datos.examen_id', self::EXAMEN)
            ->assertJsonPath('0.datos.invitador', 'Carlos Rojas');
    }

    public function test_exige_docente_invitado(): void
    {
        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [])
            ->assertUnprocessable()
            ->assertJsonPath('errors.docente_invitado_id.0', 'Seleccione un docente.');
    }

    public function test_no_permite_invitarse_a_si_mismo(): void
    {
        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::DOCENTE_CREADOR,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.docente_invitado_id.0', 'No puede invitarse a sí mismo.');
    }

    public function test_rechaza_un_usuario_que_no_es_docente(): void
    {
        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::AUXILIAR,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.docente_invitado_id.0', 'El docente invitado no es válido.');
    }

    public function test_rechaza_una_invitacion_pendiente_duplicada(): void
    {
        $this->crearInvitacion('pendiente');

        $this->assertDuplicada();
    }

    public function test_rechaza_un_examen_que_el_docente_ya_comparte(): void
    {
        $this->crearInvitacion('aceptado');

        $this->assertDuplicada();
    }

    public function test_permite_reinvitar_despues_de_un_rechazo(): void
    {
        $this->crearInvitacion('rechazado');

        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::DOCENTE_INVITADO,
        ])->assertCreated();

        $this->assertSame(2, DB::table('invitacion_examen_compartido')->count());
    }

    public function test_permite_reinvitar_despues_de_una_cancelacion(): void
    {
        $this->crearInvitacion('cancelado');

        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::DOCENTE_INVITADO,
        ])->assertCreated();

        $this->assertSame(2, DB::table('invitacion_examen_compartido')->count());
    }

    public function test_solo_el_creador_del_examen_puede_invitar(): void
    {
        $this->actingAs(Usuario::query()->findOrFail(self::OTRO_DOCENTE), 'web')
            ->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
                'docente_invitado_id' => self::DOCENTE_INVITADO,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('invitacion_examen_compartido', 0);
    }

    public function test_revierte_la_invitacion_si_falla_la_notificacion(): void
    {
        $notificaciones = Mockery::mock(NotificacionService::class);
        $notificaciones->shouldReceive('notificarInvitacionExamen')
            ->once()
            ->andThrow(new RuntimeException('Fallo controlado de notificación'));

        $servicio = new EnviarInvitacionExamenService($notificaciones);

        try {
            $servicio->ejecutar(
                Usuario::query()->findOrFail(self::DOCENTE_CREADOR),
                Examen::query()->findOrFail(self::EXAMEN),
                self::DOCENTE_INVITADO,
            );
            $this->fail('El servicio debía propagar el fallo de notificación.');
        } catch (RuntimeException $excepcion) {
            $this->assertSame('Fallo controlado de notificación', $excepcion->getMessage());
        }

        $this->assertDatabaseCount('invitacion_examen_compartido', 0);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }

    private function crearInvitacion(string $estado): void
    {
        DB::table('invitacion_examen_compartido')->insert([
            'id_invitacion' => 991001,
            'id_docente_invitador' => self::DOCENTE_CREADOR,
            'id_docente_invitado' => self::DOCENTE_INVITADO,
            'id_examen' => self::EXAMEN,
            'estado' => $estado,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertDuplicada(): void
    {
        $this->postJson(route('api.examenes.invitaciones.enviar', self::EXAMEN), [
            'docente_invitado_id' => self::DOCENTE_INVITADO,
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.docente_invitado_id.0',
                EnviarInvitacionExamenService::MENSAJE_DUPLICADA,
            );

        $this->assertDatabaseCount('invitacion_examen_compartido', 1);
        $this->assertDatabaseCount('notificacion_docente', 0);
    }
}
