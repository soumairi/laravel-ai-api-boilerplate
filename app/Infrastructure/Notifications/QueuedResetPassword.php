<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

final class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    public string $queue = 'notifications';

    public function toMail($notifiable): MailMessage
    {
        $url = (string) config('api.password_reset_url')
            .'?token='.urlencode($this->token)
            .'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Reset your password')
            ->line('You requested a password reset.')
            ->action('Reset password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire').' minutes.')
            ->line('If you did not request a reset, no action is required.');
    }
}
