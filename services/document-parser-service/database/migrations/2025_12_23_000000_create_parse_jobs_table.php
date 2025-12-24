<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('parse_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_type');
            $table->string('original_filename');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('file_size')->nullable();
            $table->integer('page_count')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('parse_jobs'); }
};
