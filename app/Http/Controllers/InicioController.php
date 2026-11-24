<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;

class InicioController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $usuario = auth()->user();
        abort_unless($usuario instanceof Usuario, 401);

        return redirect()->route(self::destino($usuario));
    }

    public static function destino(Usuario $usuario): string
    {
        return $usuario->roles->first()?->nombre_rol === Rol::NOMBRE_AUXILIAR
            ? 'auxiliar.cursos'
            : 'cursos';
    }
}
