<?php

/**
 * Check users with dots in email addresses
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Checking Users with Dots in Email ===\n\n";

// Find users with dots in email (excluding @)
$usersWithDots = User::whereRaw("email LIKE '%.%@%'")->get();

echo "Found " . $usersWithDots->count() . " users with dots in email:\n\n";

foreach ($usersWithDots as $user) {
    $dotCount = substr_count(explode('@', $user->email)[0], '.');

    echo "-------------------------------------\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Dots in local part: {$dotCount}\n";
    echo "Email verified: " . ($user->hasVerifiedEmail() ? 'Yes ✓' : 'No ✗') . "\n";
    echo "Email verified at: " . ($user->email_verified_at ?? 'null') . "\n";
    echo "Registration domain: " . ($user->registration_domain ?? 'N/A') . "\n";
    echo "Created at: {$user->created_at}\n";
    echo "\n";
}

// Check specifically for the mentioned emails
echo "\n=== Checking Specific Users ===\n\n";
$specificEmails = [
    'evgenia.k@internet.ru',
    'augustproject.bureau@gmail.com'
];

foreach ($specificEmails as $email) {
    $user = User::where('email', $email)->first();

    if ($user) {
        echo "✓ Found: {$email}\n";
        echo "  - Verified: " . ($user->hasVerifiedEmail() ? 'Yes' : 'No') . "\n";
        echo "  - Created: {$user->created_at}\n";
    } else {
        echo "✗ Not found: {$email}\n";
    }
    echo "\n";
}

echo "=== Check Complete ===\n";
