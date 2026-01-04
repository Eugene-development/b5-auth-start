<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailgunDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug Mailgun API connection and list domains';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Debugging Mailgun Configuration');
        $this->newLine();

        $domain = config('services.mailgun.domain');
        $secret = config('services.mailgun.secret');
        $endpoint = config('services.mailgun.endpoint', 'api.mailgun.net');

        $this->table(
            ['Configuration', 'Value'],
            [
                ['MAILGUN_DOMAIN', $domain ?: '❌ NOT SET'],
                ['MAILGUN_SECRET', $secret ? '✅ SET (' . substr($secret, 0, 10) . '...)' : '❌ NOT SET'],
                ['MAILGUN_ENDPOINT', $endpoint],
            ]
        );

        if (!$secret) {
            $this->error('❌ MAILGUN_SECRET is not set!');
            return 1;
        }

        $this->newLine();
        $this->info('Testing API connection...');
        $this->newLine();

        // Test 1: Check API key by listing domains
        try {
            $response = Http::withBasicAuth('api', $secret)
                ->get("https://{$endpoint}/v3/domains");

            if ($response->successful()) {
                $this->info('✅ API Key is valid!');
                $this->newLine();
                
                $domains = $response->json('items', []);
                
                if (empty($domains)) {
                    $this->warn('⚠️  No domains found in your Mailgun account');
                } else {
                    $this->info('📋 Available domains in your Mailgun account:');
                    $this->newLine();
                    
                    $tableData = [];
                    foreach ($domains as $d) {
                        $tableData[] = [
                            $d['name'],
                            $d['state'] ?? 'unknown',
                            $d['is_disabled'] ? '❌ Disabled' : '✅ Active'
                        ];
                    }
                    
                    $this->table(['Domain', 'State', 'Status'], $tableData);
                    
                    // Check if configured domain exists
                    $configuredDomain = $domain;
                    $found = false;
                    foreach ($domains as $d) {
                        if ($d['name'] === $configuredDomain) {
                            $found = true;
                            $this->newLine();
                            if ($d['state'] === 'active') {
                                $this->info("✅ Your configured domain '{$configuredDomain}' is active and verified!");
                            } else {
                                $this->warn("⚠️  Your configured domain '{$configuredDomain}' state: {$d['state']}");
                                $this->warn("Please verify your domain in Mailgun dashboard");
                            }
                            break;
                        }
                    }
                    
                    if (!$found && $configuredDomain) {
                        $this->error("❌ Your configured domain '{$configuredDomain}' NOT found in Mailgun!");
                        $this->warn("Please add and verify this domain in Mailgun dashboard, or use one of the domains listed above");
                    }
                }
            } else {
                $statusCode = $response->status();
                $this->error("❌ API Request failed with status code: {$statusCode}");
                
                if ($statusCode === 401) {
                    $this->error('Authentication failed! Your API key is incorrect.');
                    $this->newLine();
                    $this->warn('Possible issues:');
                    $this->line('1. Wrong API key - check your Mailgun dashboard');
                    $this->line('2. Using EU endpoint but configured for US (or vice versa)');
                    $this->line('3. API key expired or revoked');
                    $this->newLine();
                    $this->info('Try switching endpoints:');
                    $this->line('- For US: MAILGUN_ENDPOINT=api.mailgun.net');
                    $this->line('- For EU: MAILGUN_ENDPOINT=api.eu.mailgun.net');
                }
                
                $this->newLine();
                $this->line('Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('❌ Error connecting to Mailgun API');
            $this->error($e->getMessage());
            
            Log::error('Mailgun debug error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return 0;
    }
}
