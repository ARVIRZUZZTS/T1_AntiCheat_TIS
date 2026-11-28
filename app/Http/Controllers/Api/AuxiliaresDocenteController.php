<?php

/**
 * @file    AuxiliaresDocenteController.php
 *
 * @author  OchoaCesar <cesareduardonick@gmail.com>
 *
 * @created 2026-11-08
 *
 * @updated 2026-11-24
 *
 * @description
 * Endpoint de solo lectura que devuelve todas las cuentas de auxiliar, cada
 * una con los cursos que tiene asignados en los cursos del docente autenticado
 * y un estado derivado: ACTIVO si tiene asignaciones activas, INACTIVO si solo
 * existe su cuenta.
 *
 * El docente sale de la sesión (`$request->user()`), nunca de un parámetro:
 * así nadie puede ver asignaciones de otro docente. Solo existe la ruta GET,
 * por lo que el endpoint no permite modificar datos.
 *
 * @changelog
 * - 2026-11-08  [OchoaCesar]  feat: GET /api/docentes/me/auxiliares.
 * - 2026-11-08  [OchoaCesar]  fix: la docblock ya no referencia
 *   FuenteAuxiliares (la pestaña "Ver auxiliares" se retiró).
 * - 2026-11-24  [Equipo T1]  docs: el estado sale de las asignaciones activas.
 *
 * @see  App\Services\Auxiliar\ListarAuxiliaresDocenteService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auxiliar\ListarAuxiliaresDocenteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Presenta la lista en un arreglo plano, listo para cualquier consumidor JSON. */
class AuxiliaresDocenteController extends Controller
{
    /**
     * @param  Request  $request  Sesión del docente autenticado.
     * @param  ListarAuxiliaresDocenteService  $servicio  Proyección al contrato de la lista.
     * @return JsonResponse Todas las cuentas de auxiliar con estado ACTIVO/INACTIVO.
     */
    public function index(Request $request, ListarAuxiliaresDocenteService $servicio): JsonResponse
    {
        $filas = $servicio->ejecutar((int) $request->user()->id_usuario);

        // El contrato del endpoint se mantiene en sus 6 campos historicos; los
        // campos extra de la lista (correo, carnet, examenes...) viven en la UI.
        $contrato = array_map(fn (array $fila): array => [
            'id' => $fila['id'],
            'nombre' => $fila['nombre'],
            'apellido' => $fila['apellido'],
            'codigoSis' => $fila['codigoSis'],
            'curso' => $fila['curso'],
            'estado' => $fila['estado'],
        ], $filas);

        return response()->json($contrato);
    }
}
