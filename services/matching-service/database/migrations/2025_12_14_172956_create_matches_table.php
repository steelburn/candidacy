<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('vacancy_id');
            $table->integer('match_score')->default(0); // 0-100
            $table->json('analysis')->nullable(); // Strengths, gaps, recommendation
            $table->enum('status', ['pending', 'reviewed', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->unique(['candidate_id', 'vacancy_id']);
            $table->index('match_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
