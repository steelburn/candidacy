<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;
use App\Models\User;

echo "Testing password verification...\n\n";

$user = User::where('email', 'admin@test.com')->first();

if ($user) {
    echo "User found: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Stored password hash: {$user->password}\n";
    echo "Password length: " . strlen($user->password) . "\n";
    echo "Password starts with: " . substr($user->password, 0, 3) . "\n";

    $testPassword = 'admin123';
    echo "\nTesting password: {$testPassword}\n";
    echo "Hash check: " . (Hash::check($testPassword, $user->password) ? "PASS" : "FAIL") . "\n";

    echo "\nTesting password: admin\n";
    echo "Hash check: " . (Hash::check('admin', $user->password) ? "PASS" : "FAIL") . "\n";

    echo "\nTesting password: password\n";
    echo "Hash check: " . (Hash::check('password', $user->password) ? "PASS" : "FAIL") . "\n";

} else {
    echo "No user found with email admin@test.com\n";
}