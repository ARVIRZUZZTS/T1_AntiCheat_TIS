<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCuenta;
use App\Http\Controllers\Controller;
use App\Services\Auth\AutenticarUsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        private readonly AutenticarUsuarioService $autenticador,
    ) {
    }

    /**
     * Inicia sesión con el nombre de usuario (estándar) o el `cod_sis`
     * (alternativa) más la contraseña.
     *
     * @param  Request  $request  Trae `nombreUsuario` (identificador) y `contrasena`.
     *
     * @return JsonResponse  200 si entra; 401 si las credenciales no son
     *                       válidas; 403 si la cuenta no puede ingresar aún.
     */
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombreUsuario' => ['required', 'string'],
            'contrasena' => ['required', 'string'],
        ]);

        $usuario = $this->autenticador->porCredenciales($datos['nombreUsuario'], $datos['contrasena']);

        if ($usuario === null) {
            return response()->json(['mensaje' => 'Credenciales inválidas'], 401);
        }

        // Una cuenta del registro no entra hasta que dos docentes la aprueben (HU 26).
        $bloqueo = EstadoCuenta::mensajeAcceso($usuario->estado_cuenta);

        if ($bloqueo !== null) {
            return response()->json(['mensaje' => $bloqueo], 403);
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return response()->json(['mensaje' => 'Inicio de sesión exitoso']);
    }
}
