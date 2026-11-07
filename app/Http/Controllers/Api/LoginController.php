<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombreUsuario' => ['required', 'string'],
            'contrasena' => ['required', 'string'],
        ]);

        $nombreUsuario = trim($datos['nombreUsuario']);
        $contrasena = trim($datos['contrasena']);

        $usuario = Usuario::query()
            ->whereRaw('lower(btrim(nombre_usuario)) = lower(btrim(?))', [$nombreUsuario])
            ->first();

        if (! $usuario || ! Hash::check($contrasena, $usuario->password)) {
            return response()->json(['mensaje' => 'Credenciales inválidas'], 401);
        }

        Auth::login($usuario);
        $request->session()->regenerate();

        return response()->json(['mensaje' => 'Inicio de sesión exitoso']);
    }
}
