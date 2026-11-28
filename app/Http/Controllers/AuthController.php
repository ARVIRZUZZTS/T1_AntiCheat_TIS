<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuenta;
use App\Models\Usuario;
use App\Services\Auth\AutenticarUsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AutenticarUsuarioService $autenticador,
    ) {
    }

    /**
     * Inicia sesión por el formulario web con el nombre de usuario (estándar) o
     * el `cod_sis` (alternativa) y la contraseña.
     *
     * Acepta el identificador en `nombreUsuario` o en `cod_sis` para no romper
     * los accesos antiguos; el error siempre vuelve bajo la clave `cod_sis`, que
     * es la que muestra la pantalla de login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $identificador = (string) ($request->input('nombreUsuario') ?? $request->input('cod_sis') ?? '');
        $usuario = $this->autenticador->porCredenciales($identificador, (string) $request->input('password'));

        if ($usuario === null) {
            return back()
                ->withErrors(['cod_sis' => 'Credenciales inválidas.'])
                ->onlyInput('cod_sis', 'nombreUsuario');
        }

        // Una cuenta del registro no entra hasta que dos docentes la aprueben (HU 26).
        $bloqueo = EstadoCuenta::mensajeAcceso($usuario->estado_cuenta);

        if ($bloqueo !== null) {
            return back()->withErrors(['cod_sis' => $bloqueo])->onlyInput('cod_sis', 'nombreUsuario');
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route(InicioController::destino($usuario)));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
