<?php

/**
 * @file    ConfirmarRegistroController.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Confirmación del correo de un docente con cuenta pendiente de verificación
 * (HU 27). Abre el enlace firmado que se envió por correo: si la firma es
 * válida y la cuenta estaba pendiente de confirmación, la habilita de
 * inmediato (sin la validación manual de dos docentes) y manda al login con
 * un aviso de éxito.
 *
 * La firma se valida a mano (y no con el middleware `signed`) para que un
 * enlace vencido o alterado muestre un mensaje amable en el login en vez de
 * un 403 crudo.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  feat: creación inicial (HU 27).
 *
 * @see  App\Mail\ConfirmarRegistroDocenteMail
 * @see  App\Enums\EstadoCuenta
 */

namespace App\Http\Controllers;

use App\Enums\EstadoCuenta;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmarRegistroController extends Controller
{
    public const MENSAJE_EXITO = 'Tu cuenta fue confirmada. Ya puedes iniciar sesión.';

    public const MENSAJE_YA_CONFIRMADA = 'Tu cuenta ya estaba confirmada. Puedes iniciar sesión.';

    public const MENSAJE_ENLACE_INVALIDO = 'El enlace de confirmación no es válido o ya expiró. Vuelve a registrarte para recibir uno nuevo.';

    /**
     * Habilita la cuenta si el enlace firmado es válido.
     *
     * @param  Request  $request  Petición con la firma del enlace.
     * @param  Usuario  $usuario  Docente a confirmar (route model binding).
     *
     * @return RedirectResponse  Siempre al login, con el aviso correspondiente.
     */
    public function __invoke(Request $request, Usuario $usuario): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors(['registro' => self::MENSAJE_ENLACE_INVALIDO]);
        }

        if ($usuario->estado_cuenta === EstadoCuenta::Habilitada->value) {
            return redirect()->route('login')->with('status', self::MENSAJE_YA_CONFIRMADA);
        }

        if ($usuario->estado_cuenta !== EstadoCuenta::PendienteCorreo->value) {
            // Una cuenta externa (validación manual) o rechazada no se habilita
            // por este enlace: el enlace solo lo reciben los correos verificables.
            return redirect()->route('login')->withErrors(['registro' => self::MENSAJE_ENLACE_INVALIDO]);
        }

        $usuario->estado_cuenta = EstadoCuenta::Habilitada->value;
        $usuario->save();

        return redirect()->route('login')->with('status', self::MENSAJE_EXITO);
    }
}
