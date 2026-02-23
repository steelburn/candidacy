<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id to notification-service tables for multitenancy isolation.
 * Tables: notification_templates, notification_logs
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = ['notification_templates', 'notification_logs'];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')
                    ->comment('Tenant this record belongs to');
                $table->index('tenant_id', "idx_{$tableName}_tenant");
            });
        }
    }

    public function down(): void
    {
        $tables = ['notification_templates', 'notification_logs'];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("idx_{$tableName}_tenant");
                $table->dropColumn('tenant_id');
            });
        }
    }
};
