<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmailNotification extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        // Create simple verification URL without signed middleware
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());

        // Build frontend URL with simple parameters
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5040') . '/email-verify?' . http_build_query([
            'id' => $id,
            'hash' => $hash
        ]);

        // Use custom email template
        return (new MailMessage)
            ->subject('Подтверждение Email - BONUS5')
            ->view('emails.verification-russian', [
                'verificationUrl' => $frontendUrl,
                'user' => $notifiable
            ]);
    }
}
