<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'current_tenant_id')) {
                $table->unsignedBigInteger('current_tenant_id')->nullable()->after('remember_token')
                    ->comment('Currently active tenant for this user session');
                $table->index('current_tenant_id', 'idx_users_current_tenant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_current_tenant');
            $table->dropColumn('current_tenant_id');
        });
    }
};
