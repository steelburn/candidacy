<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full system access'
            ]
        );

        $candidateRole = Role::firstOrCreate(
            ['name' => 'candidate'],
            [
                'display_name' => 'Candidate',
                'description' => 'Job applicant role'
            ]
        );

        $recruiterRole = Role::firstOrCreate(
            ['name' => 'recruiter'],
            [
                'display_name' => 'Recruiter',
                'description' => 'Hiring manager role'
            ]
        );

        // Create permissions
        $viewDashboard = Permission::firstOrCreate(
            ['name' => 'view_dashboard'],
            [
                'display_name' => 'View Dashboard',
                'description' => 'Access the dashboard'
            ]
        );

        $manageUsers = Permission::firstOrCreate(
            ['name' => 'manage_users'],
            [
                'display_name' => 'Manage Users',
                'description' => 'Create and manage users'
            ]
        );

        $manageTenants = Permission::firstOrCreate(
            ['name' => 'manage_tenants'],
            [
                'display_name' => 'Manage Tenants',
                'description' => 'Manage tenant settings'
            ]
        );

        $viewCandidates = Permission::firstOrCreate(
            ['name' => 'view_candidates'],
            [
                'display_name' => 'View Candidates',
                'description' => 'View candidate information'
            ]
        );

        $manageCandidates = Permission::firstOrCreate(
            ['name' => 'manage_candidates'],
            [
                'display_name' => 'Manage Candidates',
                'description' => 'Create and edit candidate profiles'
            ]
        );

        // Assign permissions to roles
        $adminRole->permissions()->syncWithoutDetaching([
            $viewDashboard->id,
            $manageUsers->id,
            $manageTenants->id,
            $viewCandidates->id,
            $manageCandidates->id,
        ]);

        $recruiterRole->permissions()->syncWithoutDetaching([
            $viewDashboard->id,
            $viewCandidates->id,
        ]);

        $candidateRole->permissions()->syncWithoutDetaching([
            $viewDashboard->id,
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
