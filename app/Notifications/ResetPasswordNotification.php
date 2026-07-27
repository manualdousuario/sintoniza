<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $link = rtrim((string) config('app.url'), '/')
            .'/forget-password/reset?token='.$this->token
            .'&email='.urlencode($notifiable->email);

        // Send in the recipient's language, not the current request's.
        $locale = str_replace('-', '_', (string) ($notifiable->language ?? 'en'));

        $subject = sprintf(
            __('sintoniza.emails.password_reset_subject', [], $locale),
            config('app.name')
        );

        $body = sprintf(
            __('sintoniza.emails.password_reset_body', [], $locale),
            $notifiable->name,
            $link
        );

        return (new MailMessage)
            ->subject($subject)
            ->line($body);
    }
}
