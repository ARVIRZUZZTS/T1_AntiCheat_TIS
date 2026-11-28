<?php

/**
 * @file    CredencialesAuxiliarMail.php
 *
 * @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
 *
 * @created 2026-11-27
 *
 * @description
 * Correo que recibe el auxiliar recién creado con sus credenciales de acceso:
 * su usuario (que es el correo), la contraseña en claro y el enlace para
 * ingresar al sistema. Reutiliza la configuración y el estilo del correo de
 * confirmación de registro de docentes (mismo remitente de `MAIL_*` y la misma
 * plantilla base).
 *
 * La contraseña viaja solo en este correo, hacia el auxiliar; no se muestra en
 * pantalla ni se envía al creador.
 *
 * @changelog
 * - 2026-11-27  [Diego Tejerina]  feat: creación inicial (envío de credenciales).
 *
 * @see  App\Services\Auxiliar\CrearAuxiliarService
 * @see  resources/views/emails/credenciales-auxiliar.blade.php
 */

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CredencialesAuxiliarMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Usuario  $auxiliar         Auxiliar que recibe sus credenciales.
     * @param  string   $contrasenaPlana  Contraseña en claro (solo para este correo).
     */
    public function __construct(
        public readonly Usuario $auxiliar,
        public readonly string $contrasenaPlana,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tus credenciales de acceso — Control de Ingreso (TechOne)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.credenciales-auxiliar',
            with: [
                'nombreCompleto' => $this->nombreCompleto(),
                'usuario' => (string) $this->auxiliar->nombre_usuario,
                'contrasena' => $this->contrasenaPlana,
                'urlLogin' => route('login'),
            ],
        );
    }

    private function nombreCompleto(): string
    {
        return trim(trim((string) ($this->auxiliar->nombre ?: $this->auxiliar->nombre_usuario)).' '.trim((string) $this->auxiliar->apellido));
    }
}
