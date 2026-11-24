<?php

/**
 * @file RegistroController.php
 * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
 * @updated 2026-11-08
 * @updated 2026-11-23
 * @description Registro de cuentas docentes y sugerencias de usuario.
 * @changelog
 * - 2026-11-08  [Alisson D. Alvarado]  feat: sugerir usuarios y mostrar el estado pendiente.
 * - 2026-11-23  [Diego Tejerina]  feat: distinguir en la respuesta la verificación por
 *   correo (institucional) de la validación manual (externo) para el modal (HU 27).
 */

namespace App\Http\Controllers\Api;

use App\Enums\EstadoCuenta;
use App\Exceptions\RegistroDuplicadoException;
use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrarCuentaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroController extends Controller
{
    /** La cuenta queda pendiente hasta que dos docentes la validen (HU 26). */
    public const MENSAJE_CREADA = 'Tu cuenta aun esta en proceso de validacion. Podras ingresar cuando dos docentes la aprueben.';

    /** Correo institucional: se envió el enlace de confirmación (HU 27). */
    public const MENSAJE_VERIFICAR_CORREO = 'Te enviamos un correo electrónico para confirmar tu registro. Revisa tu bandeja de entrada para activar tu cuenta.';

    /**
     * Entrega una sugerencia sin reservar el nombre antes del registro.
     *
     * @param Request $request Datos del docente.
     * @param RegistrarCuentaService $servicio Servicio de registro.
     * @return JsonResponse Sugerencia disponible.
     * @throws \Illuminate\Validation\ValidationException Si los datos no son válidos.
     * @author Alisson D. Alvarado <alvaradoalissondalet@gmail.com>
     */
    public function sugerirUsuario(Request $request, RegistrarCuentaService $servicio): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'nombreUsuario' => $servicio->sugerirNombreUsuario($datos['nombre'], $datos['apellido']),
        ]);
    }

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

        $porCorreo = $servicio->seVerificaPorCorreo((string) $request->input('correo'));

        return response()->json([
            'verificacion' => $porCorreo ? 'correo' : 'manual',
            'mensaje' => $porCorreo
                ? self::MENSAJE_VERIFICAR_CORREO
                : EstadoCuenta::mensajeAcceso(EstadoCuenta::Pendiente),
        ], 201);
    }
}
