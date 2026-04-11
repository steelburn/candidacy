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
        if (Schema::hasTable('ai_service_mappings')) {
            return;
        }
        Schema::create('ai_service_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50);
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('model_id')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('cascade');
            $table->foreign('model_id')->references('id')->on('ai_models')->onDelete('set null');
            $table->unique(['service_type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_service_mappings');
    }
};
