<?php

/**
 * @file    EnsurePuedeGestionarAuxiliares.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Restringe "Crear auxiliar" y "Asignar auxiliar" a quienes pueden gestionar
 * auxiliares: los docentes y los auxiliares con el permiso de coordinador. Un
 * auxiliar sin permiso que entre por la URL recibe acceso denegado (403).
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (permiso de coordinador).
 *
 * @see  App\Models\Usuario::puedeGestionarAuxiliares()
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePuedeGestionarAuxiliares
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->puedeGestionarAuxiliares()) {
            abort(403);
        }

        return $next($request);
    }
}
