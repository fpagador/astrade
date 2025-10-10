<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;

    /**
     * Create a new message instance.
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function build()
    {
        return $this->subject('Restablecer contraseña')
            ->view('emails.password-reset', [
                'url' => $this->url,
            ]);
    }
}
