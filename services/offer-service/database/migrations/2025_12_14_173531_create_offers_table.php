<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('vacancy_id');
            $table->decimal('salary_offered', 12, 2);
            $table->string('currency')->default('USD');
            $table->json('benefits')->nullable();
            $table->date('start_date')->nullable();
            $table->date('offer_date');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn', 'expired'])->default('pending');
            $table->text('terms')->nullable();
            $table->text('candidate_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
