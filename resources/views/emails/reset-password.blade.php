<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif;">

<!-- Wrapper con VML para Outlook -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td align="center" bgcolor="#A06CD5">

            <!--[if gte mso 9]>
            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:100%;">
                <v:fill type="frame" src="https://dev.astrade.es/images/bg-gradient.png" color="#A06CD5" />
                <v:textbox inset="0,0,0,0">
            <![endif]-->

            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" align="center" style="padding:30px;">
                <tr>
                    <td align="center">

                        <!-- Contenedor blanco -->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center" style="background-color:#ffffff; border-radius:8px; padding:30px;">
                            <tr>
                                <td align="center">

                                    <!-- Logo -->
                                    <img src="https://dev.astrade.es/images/logo.png" alt="Logo" width="150" style="display:block; margin-bottom:20px;">

                                    <!-- Título -->
                                    <h2 style="color:#333333; margin-bottom:20px;">Hola {{ $user->name ?? '' }}!</h2>

                                    <!-- Texto -->
                                    <p style="color:#555555; font-size:15px; line-height:1.5; margin-bottom:25px;">
                                        Recibiste este correo porque solicitaste restablecer tu contraseña.
                                    </p>

                                    <!-- Botón -->
                                    <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center" style="margin-bottom:25px;">
                                        <tr>
                                            <td bgcolor="#85C7F2" align="center">
                                                <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:12px 24px; font-size:16px; color:#000000; text-decoration:none; font-weight:bold; font-family:Arial,sans-serif;">
                                                    Restablecer contraseña
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Nota -->
                                    <p style="color:#555555; font-size:14px; line-height:1.5; margin-top:25px;">
                                        Si no solicitaste este cambio, ignora este correo.
                                    </p>
                                    <p style="color:#555555; font-size:14px; line-height:1.5;">
                                        Si el botón no funciona, copia este enlace en tu navegador:
                                    </p>

                                    <p style="word-break:break-all; margin-top:10px;">
                                        <a href="{{ $url }}" style="color:#0066cc;">{{ $url }}</a>
                                    </p>

                                    <!-- Firma -->
                                    <p style="margin-top:30px; color:#333333;">
                                        Saludos,<br><strong>Talentismo</strong>
                                    </p>

                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>

            <!--[if gte mso 9]>
            </v:textbox>
            </v:rect>
            <![endif]-->

        </td>
    </tr>
</table>

</body>
</html>
