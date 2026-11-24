<?php

/**
 * @file    ConfirmarRegistroDocenteMail.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-23
 *
 * @description
 * Correo de confirmación de registro para docentes con correo institucional
 * (HU 27). Lleva un enlace firmado y temporal (24 horas) a la ruta
 * `registro.confirmar`, que al abrirse habilita la cuenta sin pasar por la
 * validación manual de dos docentes.
 *
 * El enlace se arma con `URL::temporarySignedRoute`, así funciona igual en
 * local (`APP_URL`) que en producción, y expira a las 24 horas.
 *
 * @changelog
 * - 2026-11-23  [Diego Tejerina]  feat: creación inicial (HU 27).
 *
 * @see  App\Services\Auth\RegistrarCuentaService
 * @see  resources/views/emails/confirmar-registro-docente.blade.php
 */

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Queue\SerializesModels;

class ConfirmarRegistroDocenteMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Horas que el enlace de confirmación sigue siendo válido. */
    public const HORAS_VALIDEZ = 24;

    /**
     * @param  Usuario  $docente  Docente que debe confirmar su correo.
     */
    public function __construct(public readonly Usuario $docente)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirma tu registro en Control de Ingreso (TechOne)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmar-registro-docente',
            with: [
                'nombreCompleto' => $this->nombreCompleto(),
                'urlConfirmacion' => $this->urlConfirmacion(),
            ],
        );
    }

    /**
     * Enlace firmado y temporal que habilita la cuenta al abrirlo.
     */
    private function urlConfirmacion(): string
    {
        return URL::temporarySignedRoute(
            'registro.confirmar',
            Carbon::now()->addHours(self::HORAS_VALIDEZ),
            ['usuario' => $this->docente->getKey()],
        );
    }

    private function nombreCompleto(): string
    {
        return trim(trim((string) ($this->docente->nombre ?: $this->docente->nombre_usuario)).' '.trim($this->docente->apellido));
    }
}
