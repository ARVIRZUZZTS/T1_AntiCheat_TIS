<?php

namespace App\Http\Middleware;

use App\Models\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocente
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->roles()->where('nombre_rol', Rol::NOMBRE_DOCENTE)->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
