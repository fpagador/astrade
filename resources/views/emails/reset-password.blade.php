<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif;">

<!-- Wrapper principal -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" bgcolor="#A06CD5" style="padding:30px;
        background: linear-gradient(to top right, #85C7F2, #A06CD5);">

            <!-- Central container -->
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                    <td align="center">

                        <!-- blank body -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff; border-radius:8px; padding:30px;">
                            <tr>
                                <td align="center">

                                    <!-- Logo -->
                                    <img src="{{ asset('images/logo.png') }}" alt="Logo" width="150" style="display:block; margin-bottom:20px;">

                                    <!-- Títle -->
                                    <h2 style="color:#333333; margin-bottom:20px;">Hola {{ $user->name ?? '' }}!</h2>

                                    <!-- Text -->
                                    <p style="color:#555555; font-size:15px; line-height:1.5; margin-bottom:25px;">
                                        Recibiste este correo porque solicitaste restablecer tu contraseña.
                                    </p>

                                    <!-- Button  -->
                                    <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center" style="margin-bottom:25px;">
                                        <tr>
                                            <td bgcolor="#85C7F2" align="center" style="padding:12px 24px; border-radius:6px;">
                                                <a href="{{ $url }}" target="_blank" style="
                            display:inline-block;
                            font-family:Arial, sans-serif;
                            font-size:16px;
                            font-weight:bold;
                            color:#000000;
                            text-decoration:none;
                            line-height:20px;
                        ">
                                                    Restablecer contraseña
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Note -->
                                    <p style="color:#555555; font-size:14px; line-height:1.5; margin-top:25px;">
                                        Si no solicitaste este cambio, ignora este correo.
                                    </p>
                                    <p style="color:#555555; font-size:14px; line-height:1.5;">
                                        Si el botón no funciona, copia este enlace en tu navegador:
                                    </p>

                                    <!-- Flat link -->
                                    <p style="word-break:break-all; margin-top:10px;">
                                        <a href="{{ $url }}" style="color:#0066cc; font-size:12px;">{{ $url }}</a>
                                    </p>

                                    <!-- Signature -->
                                    <p style="margin-top:30px; color:#333333;">
                                        Saludos,<br><strong>Talentismo</strong>
                                    </p>

                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
