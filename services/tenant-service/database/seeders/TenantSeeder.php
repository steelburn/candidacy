<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenant_users')->delete();
        DB::table('tenants')->delete();

        $tenantId = DB::table('tenants')->insertGetId([
            'uuid'                  => Str::uuid(),
            'name'                  => 'Default Organization',
            'slug'                  => 'default',
            'subscription_plan'     => 'free',
            'subscription_status'   => 'active',
            'max_users'             => 5,
            'max_candidates'        => 100,
            'max_vacancies'         => 10,
            'owner_id'              => 1,
            'is_active'             => true,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        DB::table('tenant_users')->insert([
            'tenant_id'  => $tenantId,
            'user_id'    => 1,
            'role'       => 'owner',
            'is_active'  => true,
            'joined_at'  => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Default tenant seeded successfully!');
        $this->command->info('Tenant: Default Organization (slug: default)');
        $this->command->info('Owner: user_id=1 (admin@test.com)');
    }
}
