<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

echo "Testing login...\n\n";

$credentials = ['email' => 'admin@test.com', 'password' => 'admin123'];

echo "Credentials: " . ($credentials['email']) . "\n";

if (!$token = JWTAuth::attempt($credentials)) {
    echo "Login failed! User not found or password incorrect.\n";
    exit(1);
}

echo "Login succeeded! Token created.\n";

$user = JWTAuth::user();

echo "User loaded: " . ($user ? $user->name : "No user") . "\n";
echo "User ID: " . $user->id . "\n";
echo "User roles: " . json_encode($user->roles->pluck('name')->toArray()) . "\n";

// Get custom claims
$payload = JWTAuth::getPayload();
echo "JWT Claims:\n";
echo "  tenant_id: " . json_encode($payload->get('tenant_id')) . "\n";
echo "  user_id: " . json_encode($payload->get('user_id')) . "\n";
echo "  roles: " . json_encode($payload->get('roles')) . "\n";

echo "\nDecoded token:\n";
print_r($payload->get());