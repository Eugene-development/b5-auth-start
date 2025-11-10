<?php

/**
 * Script to test email verification for specific users
 *
 * Usage:
 * php test-email-verification.php <email>
 *
 * Example:
 * php test-email-verification.php evgenia.k@internet.ru
 * php test-email-verification.php augustproject.bureau@gmail.com
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Log;

// Get email from command line argument
$email = $argv[1] ?? null;

if (!$email) {
    echo "Usage: php test-email-verification.php <email>\n";
    echo "Example: php test-email-verification.php evgenia.k@internet.ru\n";
    exit(1);
}

echo "=== Email Verification Test ===\n";
echo "Testing email: {$email}\n\n";

// Find user
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User not found with email: {$email}\n";
    exit(1);
}

echo "✓ User found:\n";
echo "  - ID: {$user->id}\n";
echo "  - Name: {$user->name}\n";
echo "  - Email: {$user->email}\n";
echo "  - Email verified: " . ($user->hasVerifiedEmail() ? 'Yes' : 'No') . "\n";
echo "  - Registration domain: " . ($user->registration_domain ?? 'N/A') . "\n";
echo "\n";

// Test email validation
echo "--- Email Validation Test ---\n";
$emailValid = filter_var($email, FILTER_VALIDATE_EMAIL);
echo "  PHP filter_var: " . ($emailValid ? "✓ Valid" : "❌ Invalid") . "\n";

// Laravel validation
try {
    $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
    echo "  Laravel validator: " . ($validator->passes() ? "✓ Valid" : "❌ Invalid") . "\n";
} catch (Exception $e) {
    echo "  Laravel validator: ❌ Error - " . $e->getMessage() . "\n";
}
echo "\n";

// Test email sending
echo "--- Email Sending Test ---\n";
echo "Attempting to send email verification...\n";

try {
    // Enable detailed logging
    Log::info('Testing email verification', [
        'user_id' => $user->id,
        'email' => $user->email,
        'email_has_dots' => strpos($user->email, '.') !== false
    ]);

    $user->sendEmailVerificationNotification();

    echo "✓ Email verification sent successfully!\n";
    echo "\nCheck the following:\n";
    echo "  1. Laravel logs: storage/logs/laravel.log\n";
    echo "  2. Mailgun dashboard: https://app.mailgun.com/\n";
    echo "  3. User's email inbox and spam folder\n";

} catch (Exception $e) {
    echo "❌ Failed to send email verification\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";

    Log::error('Email verification test failed', [
        'user_id' => $user->id,
        'email' => $user->email,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n";
echo "=== Test Complete ===\n";
