<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('reviewer_id');
            $table->integer('technical_score')->nullable(); // 1-10
            $table->integer('communication_score')->nullable();
            $table->integer('cultural_fit_score')->nullable();
            $table->integer('overall_score')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->text('comments')->nullable();
            $table->enum('recommendation', ['strong_hire', 'hire', 'maybe', 'no_hire'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_feedback');
    }
};
