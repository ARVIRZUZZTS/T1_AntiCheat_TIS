<?php

/**
 * @file    AuxiliarCreadoMail.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Aviso para quien corresponde saber que se creó una cuenta de auxiliar y que
 * sus credenciales ya se enviaron al auxiliar. NO lleva la contraseña. Lo
 * recibe el creador y, si el creador es un coordinador, también el docente
 * responsable.
 *
 * Reutiliza la configuración y el estilo del correo de registro de docentes.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (aviso al creador).
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 * @see  resources/views/emails/auxiliar-creado.blade.php
 */

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuxiliarCreadoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Usuario  $auxiliar  Auxiliar recién creado.
     */
    public function __construct(public readonly Usuario $auxiliar)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Se creó una cuenta de auxiliar — Control de Ingreso (TechOne)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auxiliar-creado',
            with: [
                'nombreCompleto' => $this->nombreCompleto(),
                'correoAuxiliar' => (string) $this->auxiliar->correo,
            ],
        );
    }

    private function nombreCompleto(): string
    {
        return trim(trim((string) ($this->auxiliar->nombre ?: $this->auxiliar->nombre_usuario)).' '.trim((string) $this->auxiliar->apellido));
    }
}
