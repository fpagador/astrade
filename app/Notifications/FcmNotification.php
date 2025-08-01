<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotificationResource;

class FcmNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $body;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $title, string $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('View App', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * FCM push notification representation.
     */
    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setNotification(FcmNotificationResource::create()
                ->setTitle($this->title)
                ->setBody($this->body)
            )
            ->setData([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // or the one who uses the Android app to open the notification
                // other extra data
            ]);
    }
}
