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

    public ?string $frontendUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email, ?string $frontendUrl = null)
    {
        $this->token = $token;
        $this->email = $email;
        $this->frontendUrl = $frontendUrl;
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
                'user' => $notifiable,
            ]);
    }

    /**
     * Get the reset URL for the given notifiable.
     * Use only configured frontend origins, never an unchecked request header.
     */
    protected function getResetUrl($notifiable): string
    {
        $baseUrl = $this->normalizeOrigin(config('app.frontend_url'));
        if ($baseUrl === null) {
            throw new \LogicException('A valid frontend URL is required for password reset.');
        }

        $allowedOrigins = array_filter(array_map(
            $this->normalizeOrigin(...),
            config('auth.password_reset.allowed_origins', [])
        ));
        $allowedOrigins[] = $baseUrl;

        foreach ([$this->frontendUrl, $notifiable->registration_domain] as $candidate) {
            $origin = $this->normalizeOrigin($candidate);
            if ($origin !== null && in_array($origin, $allowedOrigins, true)) {
                $baseUrl = $origin;
                break;
            }
        }

        // Build frontend URL with token and email parameters
        return $baseUrl.'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $this->email,
        ]);
    }

    private function normalizeOrigin(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));
        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || trim($parts['path'] ?? '', '/') !== '') {
            return null;
        }

        $origin = $scheme.'://'.strtolower($parts['host']);
        $port = $parts['port'] ?? null;
        if ($port !== null && $port !== ($scheme === 'https' ? 443 : 80)) {
            $origin .= ':'.$port;
        }

        return $origin;
    }
}
