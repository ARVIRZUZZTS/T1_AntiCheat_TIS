<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RegistroDuplicadoException;
use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrarCuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroController extends Controller
{
    /** La cuenta queda pendiente hasta que dos docentes la validen (HU 26). */
    public const MENSAJE_CREADA = 'Cuenta creada exitosamente. Podras ingresar cuando dos docentes validen tu cuenta.';

    public function store(Request $request, RegistrarCuentaService $servicio): JsonResponse
    {
        $errores = $servicio->validar($request->all());

        if ($errores !== []) {
            $estado = ($errores['nombreUsuario'] ?? null) === RegistrarCuentaService::MENSAJE_USUARIO_EN_USO
                ? 409
                : 422;

            return response()->json(['errores' => $errores], $estado);
        }

        try {
            $servicio->registrar($request->all());
        } catch (RegistroDuplicadoException $error) {
            return response()->json(['errores' => $error->errores()], 409);
        }

        return response()->json(['mensaje' => self::MENSAJE_CREADA], 201);
    }
}
