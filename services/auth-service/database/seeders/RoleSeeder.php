<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access and control'
            ],
            [
                'name' => 'hr_manager',
                'display_name' => 'HR Manager',
                'description' => 'Manage recruitment process and team'
            ],
            [
                'name' => 'recruiter',
                'display_name' => 'Recruiter',
                'description' => 'Manage candidates and vacancies'
            ],
            [
                'name' => 'interviewer',
                'display_name' => 'Interviewer',
                'description' => 'Conduct and manage interviews'
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Read-only access to recruitment data'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
