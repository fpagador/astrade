@component('mail::message')
    # Restablecer contraseña

    Recibiste este correo porque solicitaste restablecer tu contraseña.

    <div style="text-align:center; margin: 30px 0;">
        <a href="{{ $url }}" class="button-success"
           style="display:inline-block; padding:12px 25px; font-size:16px; font-weight:bold;
              text-decoration:none; color:#fff;
              background: linear-gradient(90deg, #85C7F2, #A06CD5);
              border-radius:6px;">
            Restablecer contraseña
        </a>
    </div>

    Si no solicitaste este cambio, ignora este correo.
@endcomponent
