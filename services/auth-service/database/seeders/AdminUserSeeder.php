<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Check if admin already exists
        $existingAdmin = User::where('email', 'admin@test.com')->first();
        
        if ($existingAdmin) {
            $this->command->info('Admin user already exists. Skipping...');
            return;
        }

        // Create admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
            $this->command->info('Admin role assigned to user.');
        }

        $this->command->info('Admin user created successfully!');
    }
}
