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
        Schema::create('candidate_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->string('token')->unique();
            $table->unsignedBigInteger('vacancy_id')->nullable(); // Logical FK to vacancy service
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('applicant_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('vacancy_id'); // Logical FK
            $table->unsignedBigInteger('question_id'); // Logical FK
            $table->text('answer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_portal_tables');
    }
};
