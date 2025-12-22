#!/usr/bin/env php
<?php
// Simple script to create a test user in auth service

require __DIR__ . '/../../services/auth-service/vendor/autoload.php';

$app = require_once __DIR__ . '/../../services/auth-service/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create test user
$user = User::updateOrCreate(
    ['email' => 'admin@test.com'],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password'),
    ]
);

echo "✓ User created: {$user->email}\n";
echo "  Password: password\n";
