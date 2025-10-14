<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif;">

<!-- Wrapper con fondo degradado (compatible con Outlook) -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
    <tr>
        <td align="center" style="background: #85C7F2;">

        <!--[if gte mso 9]>
        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:100%; height:auto;">
          <v:fill type="frame" src="{{ url('images/bg-gradient.png') }}" color="#85C7F2" />
          <v:textbox inset="0,0,0,0">
        <![endif]-->

            <div style="background-image:url('{{ url('images/bg-gradient.png') }}'); background-size:cover; background-position:center; padding:30px 10px;">

                <!-- Contenedor blanco -->
                <table role="presentation" width="500" cellspacing="0" cellpadding="0" border="0" align="center" style="background-color:#ffffff; border-radius:8px; padding:30px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
                    <tr>
                        <td align="center" style="padding:30px;">

                            <!-- Logo -->
                            <img src="{{ url('images/logo.png') }}" alt="Logo" width="150" style="margin-bottom:20px; display:block;">

                            <!-- Título -->
                            <h2 style="color:#333333; margin-bottom:20px;">Hola {{ $user->name ?? '' }}!</h2>

                            <!-- Texto -->
                            <p style="color:#555555; font-size:15px; line-height:1.5; margin-bottom:25px;">
                                Recibiste este correo porque solicitaste restablecer tu contraseña.
                            </p>

                            <!-- Botón (compatible con Outlook) -->
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center" style="margin-bottom:25px;">
                                <tr>
                                    <td bgcolor="#85C7F2" style="border-radius:6px;">
                                        <a href="{{ $url }}" target="_blank"
                                           style="display:inline-block; padding:12px 24px; font-size:16px; color:#000000;
                               text-decoration:none; font-weight:bold; font-family:Arial,sans-serif;">
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

                            <!-- Enlace plano -->
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

            </div>

            <!--[if gte mso 9]>
            </v:textbox>
            </v:rect>
            <![endif]-->

        </td>
    </tr>
</table>

</body>
</html>
