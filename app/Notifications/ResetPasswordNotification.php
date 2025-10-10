<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public string $token;

    /**
     * Create a new notification instance.
     *
     * @param string $token
     * @return void
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $buttonHtml = <<<HTML
        <a href="{$url}" class="btn-reset" style="display:inline-block;background-color:#85C7F2;color:#000000;padding:10px 20px;text-decoration:none;border-radius:6px;">
            Restablecer contraseña
        </a>
        HTML;

        return (new MailMessage)
            ->subject('Restablecer contraseña')
            ->greeting('¡Hola!')
            ->line('Recibiste este correo porque solicitaste restablecer tu contraseña.')
            ->line(new HtmlString($buttonHtml))
            ->line('Si no solicitaste este cambio, ignora este correo.')
            ->line('Si tienes problemas para hacer clic en el botón, copia y pega esta URL en tu navegador:')
            ->line($url)
            ->salutation('Saludos, Talentismo');
    }
}
