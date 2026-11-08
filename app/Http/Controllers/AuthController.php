<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCuenta;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'cod_sis' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, false)) {
            // Una cuenta del registro no entra hasta que dos docentes la aprueben (HU 26).
            $bloqueo = EstadoCuenta::mensajeAcceso(Auth::user()->estado_cuenta);

            if ($bloqueo !== null) {
                Auth::logout();

                return back()->withErrors(['cod_sis' => $bloqueo])->onlyInput('cod_sis');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('notificaciones'));
        }

        return back()
            ->withErrors(['cod_sis' => 'Credenciales inválidas.'])
            ->onlyInput('cod_sis');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
