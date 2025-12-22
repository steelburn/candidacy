<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL because modifying enum with Doctrine can be tricky without dependencies
        // and we want to ensure it works directly.
        DB::statement("ALTER TABLE matches MODIFY COLUMN status ENUM('pending', 'reviewed', 'accepted', 'rejected', 'dismissed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting not strictly necessary for this forward-fix context but good practice
        // Warning: this would fail if there are 'dismissed' values, so we might skip strict revert or clean up first.
        // For now, let's just allow it or keep 'dismissed' if we roll back (unlikely scenario for dev).
        // DB::statement("ALTER TABLE matches MODIFY COLUMN status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending'");
    }
};
