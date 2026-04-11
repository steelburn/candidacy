<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing role loading...\n\n";

$user = \App\Models\User::where('email', 'admin@test.com')->with('roles')->first();

echo "User found: " . ($user ? $user->name : "No user found") . "\n";

if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "User email: " . $user->email . "\n";
    echo "Roles loaded: " . ($user->relationLoaded('roles') ? "YES" : "NO") . "\n";
    echo "Roles count: " . $user->roles->count() . "\n";
    echo "Roles:\n";
    foreach ($user->roles as $role) {
        echo "  - {$role->name}: {$role->display_name}\n";
    }
} else {
    echo "No user found with email admin@test.com\n";
}

$role = \App\Models\Role::find(1);
echo "\nRole 1: " . ($role ? $role->name : "No role found") . "\n";

$user2 = \App\Models\User::find(1);
echo "User 1's roles:\n";
foreach ($user2->roles as $role) {
    echo "  - {$role->name}\n";
}