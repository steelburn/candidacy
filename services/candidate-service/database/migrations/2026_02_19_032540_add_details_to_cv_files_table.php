<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cv_files', function (Blueprint $table) {
            if (!Schema::hasColumn('cv_files', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('candidate_id');
            }
            if (!Schema::hasColumn('cv_files', 'stored_filename')) {
                $table->string('stored_filename')->nullable()->after('original_filename');
            }
            if (!Schema::hasColumn('cv_files', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cv_files', function (Blueprint $table) {
            $table->dropColumn(['original_filename', 'stored_filename', 'mime_type']);
        });
    }
};