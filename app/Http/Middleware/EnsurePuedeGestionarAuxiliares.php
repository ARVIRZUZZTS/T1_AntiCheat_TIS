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
 * - 2026-11-28  [Valery D. Ortuno P]  feat: parametro `crear` / `asignar` para
 *   exigir uno de los dos permisos de coordinador.
 *
 * @see  App\Models\Usuario::puedeGestionarAuxiliares()
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePuedeGestionarAuxiliares
{
    /**
     * @param  string|null  $permiso  'crear' (Crear y Eliminar auxiliar),
     *                                'asignar' (Asignar auxiliar) o null
     *                                (cualquiera de los dos).
     */
    public function handle(Request $request, Closure $next, ?string $permiso = null): Response
    {
        $usuario = $request->user();

        $permitido = $usuario !== null && match ($permiso) {
            'crear' => $usuario->puedeCrearAuxiliares(),
            'asignar' => $usuario->puedeAsignarAuxiliares(),
            default => $usuario->puedeGestionarAuxiliares(),
        };

        if (! $permitido) {
            abort(403);
        }

        return $next($request);
    }
}
