<?php

/**
 * @file    AuxiliaresDocenteController.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-08
 *
 * @description
 * Endpoint de solo lectura que devuelve los auxiliares registrados por el
 * docente autenticado, cada uno con su curso y estado normalizado.
 *
 * El docente sale de la sesión (`$request->user()`), nunca de un parámetro:
 * así nadie puede ver auxiliares de otro docente. Solo existe la ruta GET,
 * por lo que el endpoint no permite modificar datos.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: GET /api/docentes/me/auxiliares.
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 * @see  App\Livewire\Auxiliares\FuenteAuxiliares
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Presenta la lista en un arreglo plano, tal como lo espera FuenteAuxiliares. */
class AuxiliaresDocenteController extends Controller
{
    /**
     * @param  Request  $request  Sesión del docente autenticado.
     * @param  ListarAuxiliaresDocenteService  $servicio  Proyección al contrato de la lista.
     * @return JsonResponse Lista de auxiliares; vacía si el docente no tiene ninguno.
     */
    public function index(Request $request, ListarAuxiliaresDocenteService $servicio): JsonResponse
    {
        $filas = $servicio->ejecutar((int) $request->user()->id_usuario);

        return response()->json($filas);
    }
}
