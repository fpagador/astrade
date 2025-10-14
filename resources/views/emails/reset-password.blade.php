<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
</head>
<body style="margin:0; padding:30px; background-image:url('{{ asset('images/bg-gradient.png') }}'); background-size:cover; font-family: Arial, sans-serif; text-align: center;">

<!-- Contenedor blanco centrado -->
<div style="max-width:500px; margin:auto; background-color:#ffffff; border-radius:8px; padding:30px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">

    <!-- Logo -->
    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-width:150px; margin-bottom:20px;">

    <!-- Título -->
    <h2 style="color:#333333; margin-bottom:20px;">Hola {{ $user->name ?? '' }}!</h2>

    <!-- Texto -->
    <p style="color:#555555; font-size:15px; line-height:1.5; margin-bottom:25px;">
        Recibiste este correo porque solicitaste restablecer tu contraseña.
    </p>

    <!-- Botón -->
    <a href="{{ $url }}" style="display:inline-block; background-color:#85C7F2; color:#000000; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:bold; margin-bottom:25px;">
        Restablecer contraseña
    </a>

    <!-- Nota -->
    <p style="color:#555555; font-size:14px; line-height:1.5; margin-top:25px;">
        Si no solicitaste este cambio, ignora este correo.
    </p>

    <p style="color:#555555; font-size:14px; line-height:1.5;">
        Si el botón no funciona, copia este enlace en tu navegador:
    </p>

    <!-- Enlace -->
    <p style="word-break:break-all; margin-top:10px;">
        <a href="{{ $url }}" style="color:#0066cc;">{{ $url }}</a>
    </p>

    <!-- Firma -->
    <p style="margin-top:30px; color:#333333;">Saludos,<br>Talentismo</p>
</div>
</body>
</html>
