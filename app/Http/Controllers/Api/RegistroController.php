<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\RegistroDuplicadoException;
use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrarCuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroController extends Controller
{
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

        return response()->json(['mensaje' => 'Cuenta creada exitosamente'], 201);
    }
}
