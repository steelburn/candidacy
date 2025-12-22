<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('department')->nullable();
            $table->string('location');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'lead', 'executive'])->default('mid');
            $table->integer('min_experience_years')->nullable();
            $table->integer('max_experience_years')->nullable();
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->json('required_skills')->nullable();
            $table->json('preferred_skills')->nullable();
            $table->json('benefits')->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'on_hold'])->default('draft');
            $table->date('closing_date')->nullable();
            $table->integer('positions_available')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
