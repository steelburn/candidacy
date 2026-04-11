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
        if (Schema::hasTable('ai_request_logs')) {
            return;
        }
        Schema::create('ai_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50);
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->integer('duration_ms');
            $table->boolean('success');
            $table->integer('failover_attempt')->default(1);
            $table->integer('total_attempts')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('set null');
            $table->foreign('model_id')->references('id')->on('ai_models')->onDelete('set null');
            
            $table->index('service_type', 'idx_ai_logs_service');
            $table->index('provider_id', 'idx_ai_logs_provider');
            $table->index('created_at', 'idx_ai_logs_created');
            $table->index('success', 'idx_ai_logs_success');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_request_logs');
    }
};
