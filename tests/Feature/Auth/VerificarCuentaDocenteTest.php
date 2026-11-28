<?php

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
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VerificarCuentaDocenteTest extends TestCase
{
    use DatabaseTransactions;

    private const CONTRASENA = 'secreto123';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'registro.dominios_institucionales' => ['@umss.edu', '@fcyt.umss.edu.bo'],
            'registro.correos_prueba' => ['202400312@est.umss.edu'],
        ]);

        ListarSolicitudesRegistroService::olvidarPendientes();
    }

    public function test_registro_institucional_envia_confirmacion_a_la_cuenta_del_docente(): void
    {
        $respuesta = $this->registrar([
            'nombre' => 'Ana',
            'apellido' => 'Quiroga',
            'nombreUsuario' => 'ana.quiroga',
            'correo' => 'ana.quiroga@fcyt.umss.edu.bo',
            'codigoSis' => '202400111',
        ]);

        $respuesta->assertCreated()
            ->assertJson([
                'verificacion' => 'correo',
                'mensaje' => RegistroController::MENSAJE_VERIFICAR_CORREO,
            ]);

        $usuario = $this->buscar('ana.quiroga');
        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $usuario->estado_cuenta);
        $this->assertSame(1, DB::table('rol_usuario')
            ->join('rol', 'rol.id_rol', '=', 'rol_usuario.id_rol')
            ->where('rol_usuario.id_usuario', $usuario->id_usuario)
            ->where('rol.nombre_rol', 'docente')
            ->count());

        Mail::assertSent(ConfirmarRegistroDocenteMail::class, fn (ConfirmarRegistroDocenteMail $mail): bool => $mail->hasTo('ana.quiroga@fcyt.umss.edu.bo')
            && $mail->docente->id_usuario === $usuario->id_usuario);
    }

    public function test_el_correo_contiene_el_enlace_firmado_con_vigencia_de_24_horas(): void
    {
        $this->registrar([
            'nombre' => 'Luis',
            'apellido' => 'Rojas',
            'nombreUsuario' => 'luis.rojas',
            'correo' => 'luis.rojas@umss.edu',
            'codigoSis' => '202400112',
        ]);

        $cuerpo = $this->correoEnviado()->render();
        $url = $this->enlaceDelCorreo($cuerpo);
        $usuario = $this->buscar('luis.rojas');

        $this->assertStringContainsString('/registro/confirmar/'.$usuario->id_usuario, $url);
        $this->assertStringContainsString('Confirmar', $cuerpo);

        $consulta = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $consulta);

        $this->assertArrayHasKey('expires', $consulta);
        $this->assertArrayHasKey('signature', $consulta);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (string) $consulta['signature']);
        $this->assertTrue(
            Carbon::createFromTimestamp((int) $consulta['expires'])->between(
                Carbon::now()->addHours(23),
                Carbon::now()->addHours(25),
            ),
        );
    }

    public function test_el_enlace_del_correo_habilita_la_cuenta_y_permite_iniciar_sesion(): void
    {
        $this->registrar([
            'nombre' => 'María',
            'apellido' => 'Pérez',
            'nombreUsuario' => 'maria.perez',
            'correo' => 'maria.perez@umss.edu',
            'codigoSis' => '202400113',
        ]);

        $usuario = $this->buscar('maria.perez');
        $url = $this->enlaceDelCorreo($this->correoEnviado()->render());

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', ConfirmarRegistroController::MENSAJE_EXITO);

        $this->assertSame(EstadoCuenta::Habilitada->value, $this->buscar('maria.perez')->estado_cuenta);

        $this->postJson(route('api.auth.login'), [
            'nombreUsuario' => 'maria.perez',
            'contrasena' => self::CONTRASENA,
        ])->assertOk();

        $this->assertAuthenticatedAs(Usuario::findOrFail($usuario->id_usuario));
    }

    public function test_un_enlace_con_firma_alterada_se_rechaza_con_mensaje_claro(): void
    {
        $this->registrar([
            'nombreUsuario' => 'firma.mala',
            'correo' => 'firma.mala@umss.edu',
            'codigoSis' => '202400114',
        ]);

        $url = $this->enlaceConfirmacion($this->buscar('firma.mala')->id_usuario);
        $urlAlterada = str_replace('signature=', 'signature=ff', $url);

        $this->get($urlAlterada)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $this->buscar('firma.mala')->estado_cuenta);
        $this->intentarLogin('firma.mala')->assertForbidden();
    }

    public function test_un_enlace_expirado_se_rechaza_con_mensaje_claro(): void
    {
        $this->registrar([
            'nombreUsuario' => 'enlace.viejo',
            'correo' => 'enlace.viejo@umss.edu',
            'codigoSis' => '202400115',
        ]);

        $urlExpirada = URL::temporarySignedRoute(
            'registro.confirmar',
            Carbon::now()->subMinute(),
            ['usuario' => $this->buscar('enlace.viejo')->id_usuario],
        );

        $this->get($urlExpirada)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::PendienteCorreo->value, $this->buscar('enlace.viejo')->estado_cuenta);
        $this->intentarLogin('enlace.viejo')->assertForbidden();
    }

    public function test_un_correo_no_institucional_no_envia_nada_y_pasa_a_validacion_manual(): void
    {
        $respuesta = $this->registrar([
            'nombreUsuario' => 'externo.test',
            'correo' => 'externo@gmail.com',
            'codigoSis' => '202400116',
        ]);

        $respuesta->assertCreated()
            ->assertJson([
                'verificacion' => 'manual',
                'mensaje' => EstadoCuenta::mensajeAcceso(EstadoCuenta::Pendiente),
            ]);

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->buscar('externo.test')->estado_cuenta);
        Mail::assertNothingSent();
    }

    public function test_un_enlace_valido_no_habilita_una_cuenta_pendiente_de_validacion_manual(): void
    {
        $this->registrar([
            'nombreUsuario' => 'manual.test',
            'correo' => 'manual@gmail.com',
            'codigoSis' => '202400117',
        ]);

        $url = $this->enlaceConfirmacion($this->buscar('manual.test')->id_usuario);

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::Pendiente->value, $this->buscar('manual.test')->estado_cuenta);
        $this->intentarLogin('manual.test')->assertForbidden();
    }

    public function test_un_enlace_valido_no_habilita_una_cuenta_rechazada(): void
    {
        $this->registrar([
            'nombreUsuario' => 'rechazado.test',
            'correo' => 'rechazado@umss.edu',
            'codigoSis' => '202400118',
        ]);

        $usuario = $this->buscar('rechazado.test');
        $usuario->estado_cuenta = EstadoCuenta::Rechazada->value;
        $usuario->save();

        $this->get($this->enlaceConfirmacion($usuario->id_usuario))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['registro' => ConfirmarRegistroController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertSame(EstadoCuenta::Rechazada->value, $this->buscar('rechazado.test')->estado_cuenta);
    }

    public function test_reutilizar_el_enlace_no_vuelve_a_confirmar_la_cuenta(): void
    {
        $this->registrar([
            'nombreUsuario' => 'reuso.test',
            'correo' => 'reuso@umss.edu',
            'codigoSis' => '202400119',
        ]);

        $url = $this->enlaceConfirmacion($this->buscar('reuso.test')->id_usuario);

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', ConfirmarRegistroController::MENSAJE_EXITO);

        $this->get($url)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', ConfirmarRegistroController::MENSAJE_YA_CONFIRMADA);

        $this->assertSame(EstadoCuenta::Habilitada->value, $this->buscar('reuso.test')->estado_cuenta);
        Mail::assertSent(ConfirmarRegistroDocenteMail::class, 1);
    }

    private function registrar(array $datos): TestResponse
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

    private function intentarLogin(string $nombreUsuario): TestResponse
    {
        return $this->postJson(route('api.auth.login'), [
            'nombreUsuario' => $nombreUsuario,
            'contrasena' => self::CONTRASENA,
        ]);
    }

    private function buscar(string $nombreUsuario): Usuario
    {
        return Usuario::query()->where('nombre_usuario', $nombreUsuario)->firstOrFail();
    }

    private function correoEnviado(): ConfirmarRegistroDocenteMail
    {
        $mail = Mail::sent(ConfirmarRegistroDocenteMail::class)->first();

        $this->assertInstanceOf(ConfirmarRegistroDocenteMail::class, $mail);

        return $mail;
    }

    private function enlaceDelCorreo(string $cuerpo): string
    {
        $encontrado = preg_match('/href="([^"]*\/registro\/confirmar\/[^"]*)"/', $cuerpo, $coincidencias);

        $this->assertSame(1, $encontrado, 'El correo no contiene el enlace de confirmación.');

        return html_entity_decode((string) $coincidencias[1], ENT_QUOTES, 'UTF-8');
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
