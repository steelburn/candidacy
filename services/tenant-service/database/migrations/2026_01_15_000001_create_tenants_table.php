<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('logo_url', 500)->nullable();
            $table->json('settings')->nullable();
            $table->string('subscription_plan', 50)->default('free');
            $table->string('subscription_status', 50)->default('active');
            $table->timestamp('subscription_ends_at')->nullable();
            $table->integer('max_users')->default(5);
            $table->integer('max_candidates')->default(100);
            $table->integer('max_vacancies')->default(10);
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug', 'idx_tenants_slug');
            $table->index('domain', 'idx_tenants_domain');
            $table->index('is_active', 'idx_tenants_is_active');
            $table->index('subscription_status', 'idx_tenants_subscription');
            $table->index('uuid', 'idx_tenants_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
