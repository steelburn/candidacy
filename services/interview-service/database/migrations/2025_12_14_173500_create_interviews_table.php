<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('vacancy_id');
            $table->unsignedBigInteger('interviewer_id')->nullable();
            $table->enum('stage', ['screening', 'technical', 'behavioral', 'final'])->default('screening');
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('location')->nullable(); // or video link
            $table->enum('type', ['in_person', 'video', 'phone'])->default('video');
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
