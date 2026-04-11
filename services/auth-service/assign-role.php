<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('email', 'admin@test.com')->first();
$role = \App\Models\Role::where('name', 'admin')->first();

if ($user && $role) {
    $user->roles()->attach($role->id);
    echo "✅ Role 'admin' assigned to user '{$user->name}' ({$user->email})\n";
} else {
    if (!$user) {
        echo "❌ User not found\n";
    }
    if (!$role) {
        echo "❌ Role 'admin' not found\n";
    }
}

echo "\nAll roles:\n";
foreach (\App\Models\Role::all() as $role) {
    echo "  - {$role->name}: {$role->display_name}\n";
}

echo "\nUser roles:\n";
$user = \App\Models\User::where('email', 'admin@test.com')->first();
foreach ($user->roles as $role) {
    echo "  - {$role->name}\n";
}