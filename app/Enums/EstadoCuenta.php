<?php

/**
 * @file    EstadoCuenta.php
 *
 * @author  Valery D. Ortuno P <valerydariana98@gmail.com>
 *
 * @created 2026-11-08
 *
 * @description
 * Estado de la cuenta de un usuario (`usuario.estado_cuenta`). Una cuenta
 * creada desde el registro con correo externo nace pendiente y no puede
 * ingresar hasta que dos docentes la aprueben (queda habilitada); con una
 * desaprobación queda rechazada. Si el correo es institucional nace
 * pendiente de confirmación por correo y se habilita sola cuando el docente
 * confirma el enlace que se le envió (HU 27), sin pasar por la validación
 * manual. Las cuentas que crea el sistema (docentes iniciales, auxiliares)
 * nacen habilitadas.
 *
 * @changelog
 * - 2026-11-08  [Valery D. Ortuno P]  feat: creación inicial (HU 26).
 * - 2026-11-23  [Diego Tejerina]  feat: estado `pendiente_correo` para las cuentas que se
 *   verifican por correo electrónico (HU 27); no entran a la bandeja de
 *   validación manual de HU 26.
 *
 * @see  App\Services\Docente\ValidarRegistroDocenteService
 * @see  App\Services\Auth\VerificarCorreoInstitucionalService
 * @see  database/migrations/2026_11_08_000001_validacion_registro_docente.php
 */

namespace App\Enums;

enum EstadoCuenta: string
{
    case Pendiente = 'pendiente';
    case PendienteCorreo = 'pendiente_correo';
    case Habilitada = 'habilitada';
    case Rechazada = 'rechazada';

    /**
     * Mensaje del login para una cuenta que no puede ingresar, o null si puede.
     *
     * @param  self|string|null  $estado  Estado de la cuenta (null: cuenta anterior a la columna).
     */
    public static function mensajeAcceso(self|string|null $estado): ?string
    {
        $estado = $estado instanceof self ? $estado : self::tryFrom((string) $estado);

        return match ($estado) {
            self::Pendiente => 'Tu cuenta aun esta en proceso de validacion. Podras ingresar cuando dos docentes la aprueben.',
            self::PendienteCorreo => 'Debes confirmar tu correo electrónico antes de iniciar sesión.',
            self::Rechazada => 'Tu solicitud de registro fue rechazada.',
            default => null,
        };
    }
}
