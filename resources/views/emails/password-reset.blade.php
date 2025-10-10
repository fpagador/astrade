<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="background:#f5f5f5; padding:20px;">
<div style="background:white; padding:30px; border-radius:8px; max-width:600px; margin:auto; text-align:center; font-family:sans-serif; color:#333;">
    <h1 style="margin-bottom:20px;">¡Hola!</h1>

    <p style="font-size:16px; margin-bottom:30px;">
        Recibiste este correo porque solicitaste restablecer tu contraseña.
    </p>

    <div style="margin-bottom:25px;">
        <a href="{{ $url }}" class="button-success"
           style="display:inline-block; padding:14px 28px; font-size:16px; font-weight:bold;
                      text-decoration:none; color:#fff;
                      background: linear-gradient(90deg, #85C7F2, #A06CD5);
                      border-radius:6px;">
            Restablecer contraseña
        </a>
    </div>

    <p style="font-size:14px; color:#555;">
        Si no solicitaste este cambio, ignora este correo.
    </p>
</div>
</body>
</html>
