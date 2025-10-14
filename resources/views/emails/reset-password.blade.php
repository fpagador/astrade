<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif;">

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" bgcolor="#85C7F2" style="padding:30px;
        background: linear-gradient(to top right, #85C7F2, #A06CD5);">

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; border-radius:8px; padding:30px;">
                <tr>
                    <td align="center">
                        <!-- Logo -->
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" width="150" style="display:block; margin-bottom:20px;">

                        <!-- Texto -->
                        <h2 style="color:#333333; margin-bottom:20px;">Hola {{ $user->name ?? '' }}!</h2>
                        <p style="color:#555555; font-size:15px; line-height:1.5; margin-bottom:25px;">
                            Recibiste este correo porque solicitaste restablecer tu contraseña.
                        </p>

                        <!-- Botón -->
                        <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center">
                            <tr>
                                <td bgcolor="#85C7F2" align="center">
                                    <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:12px 24px; font-size:16px; color:#000; text-decoration:none; font-weight:bold; font-family:Arial,sans-serif;">
                                        Restablecer contraseña
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="color:#555555; font-size:14px; line-height:1.5; margin-top:25px;">
                            Si no solicitaste este cambio, ignora este correo.
                        </p>
                        <p style="color:#555555; font-size:14px; line-height:1.5;">
                            Si el botón no funciona, copia este enlace en tu navegador:
                        </p>
                        <p style="word-break:break-all; margin-top:10px;">
                            <a href="{{ $url }}" style="color:#0066cc;">{{ $url }}</a>
                        </p>

                        <p style="margin-top:30px; color:#333333;">
                            Saludos,<br><strong>Talentismo</strong>
                        </p>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
