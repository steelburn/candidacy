<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add tenant_id to auth.users table for multitenancy support.
     * Users can belong to multiple tenants via tenant_users table,
     * but the current_tenant_id indicates the active tenant context.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')
                    ->comment('Current active tenant for this user');
                $table->index('tenant_id', 'idx_users_tenant');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_tenant');
                $table->dropColumn('tenant_id');
            });
        }
    }
};
