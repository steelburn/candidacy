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
        if (Schema::hasTable('ai_models')) {
            return;
        }
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('name', 100);
            $table->string('display_name', 150)->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->json('capabilities')->nullable();
            $table->integer('context_length')->nullable();
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('ai_providers')->onDelete('cascade');
            $table->unique(['provider_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
