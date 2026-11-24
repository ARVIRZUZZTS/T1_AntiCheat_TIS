{{--
    @file    confirmar-registro-docente.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-23
    @description
    Cuerpo del correo de confirmación de registro de docente (HU 27). Estilos
    en línea porque los clientes de correo no cargan hojas externas. El botón
    «Confirmar» apunta al enlace firmado que habilita la cuenta.
    @changelog
    - 2026-11-23  [Diego Tejerina]  feat: creación inicial (HU 27).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirma tu registro</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:Arial, Helvetica, sans-serif; color:#1e293b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:560px; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.1);">
                    <tr>
                        <td style="background-color:#1d4ed8; padding:24px 32px;">
                            <p style="margin:0; font-size:18px; font-weight:bold; color:#ffffff;">
                                Control de Ingreso — TechOne
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; font-size:16px;">
                                Hola {{ $nombreCompleto }},
                            </p>
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#334155;">
                                Recibimos tu registro como docente en
                                <strong>Control de Ingreso (TechOne)</strong>. Para activar tu cuenta
                                necesitamos que confirmes que este correo electrónico es tuyo.
                            </p>
                            <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#334155;">
                                Haz clic en el botón para confirmar tu correo y habilitar tu acceso al sistema.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" style="border-radius:8px; background-color:#1d4ed8;">
                                        <a href="{{ $urlConfirmacion }}"
                                           style="display:inline-block; padding:14px 40px; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">
                                            Confirmar
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:24px 0 8px; font-size:13px; line-height:1.6; color:#64748b;">
                                Este enlace caduca en {{ \App\Mail\ConfirmarRegistroDocenteMail::HORAS_VALIDEZ }} horas. Si el botón no funciona,
                                copia y pega esta dirección en tu navegador:
                            </p>
                            <p style="margin:0 0 8px; font-size:13px; line-height:1.6; word-break:break-all;">
                                <a href="{{ $urlConfirmacion }}" style="color:#1d4ed8;">{{ $urlConfirmacion }}</a>
                            </p>
                            <p style="margin:24px 0 0; font-size:13px; line-height:1.6; color:#64748b;">
                                Si no solicitaste este registro, puedes ignorar este mensaje.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc; padding:16px 32px; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; font-size:12px; color:#94a3b8;">
                                © {{ date('Y') }} Tech One SRL — Control de Ingreso a Exámenes
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
