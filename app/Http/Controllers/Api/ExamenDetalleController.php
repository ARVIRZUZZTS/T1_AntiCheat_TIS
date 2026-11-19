<?php

/**
 * @file    ExamenDetalleController.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-19
 *
 * @description
 * Endpoint del modal "Ver detalle" de Ver examenes: devuelve los datos del
 * examen, sus reglas y los materiales permitidos. Es de solo lectura; lo
 * puede ver quien lo creo, el docente de uno de sus cursos o el invitado que
 * acepto. El servicio revisa ese permiso en su misma consulta (403 si no), asi
 * que aqui no se carga el examen por la ruta: cada consulta a la base remota
 * pesa ~380 ms.
 *
 * @changelog
 * - 2026-11-19  [Valery D. Ortuno P]  feat: creacion inicial.
 *
 * @see  App\Services\Examen\ObtenerDetalleExamenService
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Examen\ObtenerDetalleExamenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamenDetalleController extends Controller
{
    public function mostrar(Request $request, int $examen, ObtenerDetalleExamenService $servicio): JsonResponse
    {
        $datos = $servicio->ejecutar($examen, (int) $request->user()->id_usuario);
        abort_if($datos === null, 404);

        return response()->json(['datos' => $datos]);
    }
}
