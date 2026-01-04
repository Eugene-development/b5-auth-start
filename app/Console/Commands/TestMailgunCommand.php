<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestMailgunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : Email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email via Mailgun to verify configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info('Checking Mailgun configuration...');
        $this->newLine();

        // Check if Mailgun is configured
        $mailer = config('mail.default');
        $mailgunDomain = config('services.mailgun.domain');
        $mailgunSecret = config('services.mailgun.secret');
        $mailFrom = config('mail.from.address');

        $this->table(
            ['Configuration', 'Value'],
            [
                ['MAIL_MAILER', $mailer],
                ['MAILGUN_DOMAIN', $mailgunDomain ?: '❌ NOT SET'],
                ['MAILGUN_SECRET', $mailgunSecret ? '✅ SET (hidden)' : '❌ NOT SET'],
                ['MAIL_FROM_ADDRESS', $mailFrom],
            ]
        );

        if (!$mailgunDomain || !$mailgunSecret) {
            $this->error('❌ Mailgun is not properly configured!');
            $this->warn('Please set MAILGUN_DOMAIN and MAILGUN_SECRET in your .env file');
            return 1;
        }

        $this->newLine();
        $this->info("Sending test email to: {$email}");
        $this->newLine();

        try {
            Mail::raw('This is a test email from BONUS5 to verify Mailgun configuration.', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - Mailgun Configuration Check');
            });

            $this->info('✅ Email sent successfully!');
            $this->info('Please check your inbox (and spam folder) for the test email.');
            
            Log::info('Test email sent successfully via Mailgun', [
                'email' => $email,
                'mailer' => $mailer
            ]);

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            
            Log::error('Failed to send test email via Mailgun', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->warn('Common issues:');
            $this->line('1. Check your MAILGUN_SECRET (API key) is correct');
            $this->line('2. Check your MAILGUN_DOMAIN is correct');
            $this->line('3. Check your sender email is authorized in Mailgun');
            $this->line('4. Check Mailgun API endpoint is reachable');

            return 1;
        }
    }
}
