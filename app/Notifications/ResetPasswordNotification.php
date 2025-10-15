<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;
    public $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = $this->getResetUrl($notifiable);

        // Use custom email template
        return (new MailMessage)
            ->subject('Восстановление пароля - BONUS5')
            ->view('emails.reset-password-russian', [
                'resetUrl' => $resetUrl,
                'user' => $notifiable
            ]);
    }

    /**
     * Get the reset URL for the given notifiable.
     * Uses registration domain if available, otherwise fallback to FRONTEND_URL
     */
    protected function getResetUrl($notifiable): string
    {
        // Use registration domain if available, otherwise fallback to FRONTEND_URL
        $baseUrl = $notifiable->registration_domain ?? env('FRONTEND_URL', 'http://localhost:5040');

        // Build frontend URL with token and email parameters
        return $baseUrl . '/reset-password?' . http_build_query([
            'token' => $this->token,
            'email' => $this->email
        ]);
    }
}
