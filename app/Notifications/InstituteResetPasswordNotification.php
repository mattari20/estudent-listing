<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class InstituteResetPasswordNotification extends ResetPassword
{
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('institutes.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset your eStudent Institutes password')
            ->greeting('Hello!')
            ->line('We received a request to reset the password for your eStudent Institutes account.')
            ->action('Reset password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire', 60).' minutes and can only be used once.')
            ->line('If you did not request a password reset, you can safely ignore this email. Your current password will not change unless the link is used.');
    }
}
