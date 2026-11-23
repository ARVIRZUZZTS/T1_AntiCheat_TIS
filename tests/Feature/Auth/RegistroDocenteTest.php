<?php

/**
 * @file    RegistroDocenteTest.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Pruebas del registro de docentes (HU 27): la bifurcación por correo
 * (institucional / de prueba vs externo), el envío del correo de
 * confirmación, el estado con que nace cada cuenta, el enlace firmado que
 * habilita la cuenta, el bloqueo del login hasta confirmar y las validaciones
 * del formulario.
 *
 * Corren contra la base Postgres local de pruebas (el registro usa LOCK TABLE
 * y sequences propios de Postgres) dentro de una transacción que se revierte.
 * La configuración de dominios se fija en el test para no depender del `.env`.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  test: creación inicial (HU 27).
 *
 * @see App\Services\Auth\RegistrarCuentaService
 * @see App\Http\Controllers\Api\RegistroController
 * @see App\Http\Controllers\ConfirmarRegistroController
 */

namespace Tests\Feature\Auth;

use App\Enums\EstadoCuenta;
use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\ConfirmarRegistroController;
use App\Mail\ConfirmarRegistroDocenteMail;
use App\Models\Usuario;
use App\Services\Docente\ListarSolicitudesRegistroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegistroDocenteTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTRASENA = 'secreto123';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // Fijamos los dominios elegibles acá: el test no depende del `.env`.
        config([
            'registro.dominios_institucionales' => ['@umss.edu', '@fcyt.umss.edu.bo'],
            'registro.correos_prueba' => ['202400312@est.umss.edu'],
        ]);

        ListarSolicitudesRegistroService::olvidarPendientes();
    }

    public function test_un_correo_institucional_queda_pendiente_de_correo_y_recibe_el_enlace(): void
    {
        $respuesta = $this->registrar(['correo' => 'nuevo.docente@umss.edu', 'nombreUsuario' => 'nuevo.docente']);

        $respuesta->assertCreated()
            ->assertJson([
                'verificacion' => 'correo',
                'mensaje' => RegistroController::MENSAJE_VERIFICAR_CORREO,
            ]);

        $usuario = $this->buscar('nuevo.docente');
        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $usuario->estado_cuenta);
        // No entra a la bandeja de validación manual de HU 26.
        $this->assertSame(0, DB::table('usuario')
            ->where('estado_cuenta', EstadoCuenta::Pendiente->value)
            ->where('nombre_usuario', 'nuevo.docente')->count());

        Mail::assertSent(ConfirmarRegistroDocenteMail::class, fn (ConfirmarRegistroDocenteMail $mail): bool => $mail->hasTo('nuevo.docente@umss.edu')
            && $mail->docente->id_usuario === $usuario->id_usuario);
    }

    public function test_los_dominios_de_facultad_y_el_correo_de_prueba_tambien_se_verifican_por_correo(): void
    {
        foreach ([
            ['correo' => 'ana.quiroga@fcyt.umss.edu.bo', 'nombreUsuario' => 'ana.quiroga', 'codigoSis' => '202400111'],
            ['correo' => '202400312@est.umss.edu', 'nombreUsuario' => 'est.prueba', 'codigoSis' => '202400312'],
        ] as $datos) {
            $this->registrar($datos)->assertCreated()->assertJson(['verificacion' => 'correo']);
            $this->assertSame(EstadoCuenta::PendienteCorreo->value, $this->buscar($datos['nombreUsuario'])->estado_cuenta);
        }

        Mail::assertSent(ConfirmarRegistroDocenteMail::class, 2);
    }

    public function test_un_correo_externo_queda_pendiente_de_validacion_manual_y_sin_correo(): void
    {
        $respuesta = $this->registrar(['correo' => 'profe.externo@gmail.com', 'nombreUsuario' => 'profe.externo']);

        $respuesta->assertCreated()
            ->assertJson([
                'verificacion' => 'manual',
                'mensaje' => EstadoCuenta::mensajeAcceso(EstadoCuenta::Pendiente),
            ]);

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->buscar('profe.externo')->estado_cuenta);
        Mail::assertNothingSent();
    }

    public function test_el_docente_institucional_no_puede_ingresar_antes_de_confirmar(): void
    {
        $this->registrar(['correo' => 'sin.confirmar@umss.edu', 'nombreUsuario' => 'sin.confirmar']);

        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'sin.confirmar', 'contrasena' => self::CONTRASENA])
            ->assertForbidden()
            ->assertJson(['mensaje' => EstadoCuenta::mensajeAcceso(EstadoCuenta::PendienteCorreo)]);

        $this->assertGuest();
    }

    public function test_el_enlace_firmado_habilita_la_cuenta_y_permite_ingresar(): void
    {
        $this->registrar(['correo' => 'confirma@umss.edu', 'nombreUsuario' => 'confirma']);
        $usuario = $this->buscar('confirma');

        $this->get($this->enlaceConfirmacion($usuario->id_usuario))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', ConfirmarRegistroController::MENSAJE_EXITO);

        $this->assertSame(EstadoCuenta::Habilitada->value, $this->buscar('confirma')->estado_cuenta);

        $this->postJson(route('api.auth.login'), ['nombreUsuario' => 'confirma', 'contrasena' => self::CONTRASENA])
            ->assertOk();
        $this->assertAuthenticatedAs(Usuario::findOrFail($usuario->id_usuario));
    }

    public function test_un_enlace_sin_firma_valida_avisa_en_el_login_y_no_habilita(): void
    {
        $this->registrar(['correo' => 'intruso@umss.edu', 'nombreUsuario' => 'intruso']);
        $usuario = $this->buscar('intruso');

        // Sin la firma (o con una alterada) el enlace no es válido.
        $this->get(route('registro.confirmar', ['usuario' => $usuario->id_usuario]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $this->buscar('intruso')->estado_cuenta);
    }

    public function test_un_enlace_expirado_avisa_en_el_login(): void
    {
        $this->registrar(['correo' => 'tarde@umss.edu', 'nombreUsuario' => 'tarde']);
        $usuario = $this->buscar('tarde');

        $urlExpirada = URL::temporarySignedRoute('registro.confirmar', Carbon::now()->subMinute(), ['usuario' => $usuario->id_usuario]);

        $this->get($urlExpirada)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $this->buscar('tarde')->estado_cuenta);
    }

    public function test_rechaza_los_campos_obligatorios(): void
    {
        $this->postJson(route('api.auth.registro'), [])
            ->assertStatus(422)
            ->assertJsonStructure(['errores' => ['nombre', 'apellido', 'nombreUsuario', 'correo', 'codigoSis', 'contrasena', 'confirmarContrasena']]);

        Mail::assertNothingSent();
    }

    public function test_rechaza_un_nombre_de_usuario_duplicado(): void
    {
        $this->registrar(['correo' => 'primero@umss.edu', 'nombreUsuario' => 'repetido']);

        $this->registrar(['correo' => 'segundo@umss.edu', 'nombreUsuario' => 'repetido', 'codigoSis' => '202400999'])
            ->assertStatus(409)
            ->assertJsonPath('errores.nombreUsuario', \App\Services\Auth\RegistrarCuentaService::MENSAJE_USUARIO_EN_USO);
    }

    public function test_rechaza_una_contrasena_corta_y_una_confirmacion_distinta(): void
    {
        $this->registrar(['correo' => 'corta@umss.edu', 'nombreUsuario' => 'corta', 'contrasena' => '123', 'confirmarContrasena' => '123'])
            ->assertStatus(422)
            ->assertJsonPath('errores.contrasena', \App\Services\Auth\RegistrarCuentaService::MENSAJE_CONTRASENA_CORTA);

        $this->registrar(['correo' => 'otra@umss.edu', 'nombreUsuario' => 'otra', 'contrasena' => 'clavelarga1', 'confirmarContrasena' => 'distinta2'])
            ->assertStatus(422)
            ->assertJsonPath('errores.confirmarContrasena', \App\Services\Auth\RegistrarCuentaService::MENSAJE_CONTRASENAS_DISTINTAS);
    }

    /**
     * @param  array<string, string>  $datos
     */
    private function registrar(array $datos): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('api.auth.registro'), array_merge([
            'nombre' => 'Nuevo',
            'apellido' => 'Docente',
            'nombreUsuario' => 'nuevo.docente',
            'correo' => 'nuevo.docente@umss.edu',
            'codigoSis' => '202400312',
            'contrasena' => self::CONTRASENA,
            'confirmarContrasena' => self::CONTRASENA,
        ], $datos));
    }

    private function buscar(string $nombreUsuario): Usuario
    {
        return Usuario::query()->where('nombre_usuario', $nombreUsuario)->firstOrFail();
    }

    private function enlaceConfirmacion(int $idUsuario): string
    {
        return URL::temporarySignedRoute(
            'registro.confirmar',
            Carbon::now()->addHours(ConfirmarRegistroDocenteMail::HORAS_VALIDEZ),
            ['usuario' => $idUsuario],
        );
    }
}
