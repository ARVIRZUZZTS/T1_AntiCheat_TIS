{{--
    @file    auxiliar-creado.blade.php
    @author  Diego Tejerina <josediegotejerinamolina@gmail.com>
    @created 2026-11-27
    @description
    Aviso al creador (y al docente responsable si el creador es coordinador) de
    que se creó la cuenta de auxiliar y las credenciales se enviaron al auxiliar.
    NO incluye la contraseña. Estilos en línea.
    @changelog
    - 2026-11-27  [Diego Tejerina]  feat: creación inicial (aviso al creador).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta de auxiliar creada</title>
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
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#334155;">
                                Se creó la cuenta de auxiliar de
                                <strong>{{ $nombreCompleto }}</strong>
                                ({{ $correoAuxiliar }}) y sus credenciales de acceso ya
                                se enviaron a su correo.
                            </p>
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#64748b;">
                                Por seguridad, este aviso no incluye la contraseña. Si el
                                auxiliar no recibe el correo, puedes reenviar sus
                                credenciales desde la lista de auxiliares.
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
