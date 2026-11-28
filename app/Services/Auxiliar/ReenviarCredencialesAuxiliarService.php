<?php

/**
 * @file    ReenviarCredencialesAuxiliarService.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Reenvía las credenciales de un auxiliar: como la contraseña se guarda con
 * hash y no se puede recuperar, se regenera con la misma regla del alta
 * (`GeneradorContrasenaAuxiliarService`), se guarda el nuevo hash y se envía al
 * correo del auxiliar. El documento para la contraseña es el carnet (cédula);
 * si el auxiliar no tiene carnet, la contraseña es aleatoria.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (reenvío = reseteo).
 *
 * @see  App\Services\Auxiliar\GeneradorContrasenaAuxiliarService
 * @see  App\Mail\CredencialesAuxiliarMail
 */

namespace App\Services\Auxiliar;

use App\Mail\CredencialesAuxiliarMail;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ReenviarCredencialesAuxiliarService
{
    public function __construct(
        private readonly GeneradorContrasenaAuxiliarService $generadorContrasena,
    ) {
    }

    /**
     * Regenera y reenvía las credenciales del auxiliar.
     *
     * @param  Usuario  $auxiliar  Auxiliar al que se le reenvían las credenciales.
     *
     * @return bool  true si el correo se pudo enviar; false si falló (la
     *               contraseña igual quedó reseteada).
     *
     * @author Diego Tejerina <josediegotejerinamolina@gmail.com>
     * @since  2026-11-27
     */
    public function reenviar(Usuario $auxiliar): bool
    {
        $contrasena = $this->generadorContrasena->generar(
            (string) ($auxiliar->nombre ?: $auxiliar->nombre_usuario),
            (string) $auxiliar->apellido,
            (string) $auxiliar->carnet,
        );

        $auxiliar->password = Hash::make($contrasena);
        $auxiliar->save();

        try {
            Mail::to((string) $auxiliar->correo)->send(new CredencialesAuxiliarMail($auxiliar, $contrasena));

            return true;
        } catch (\Throwable $error) {
            report($error);

            return false;
        }
    }
}
