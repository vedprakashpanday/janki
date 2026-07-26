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
        // Column type ko TEXT mein badal rahe hain taaki comma-separated IDs aa sakein
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `proof_media_id` TEXT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke liye wapas BIGINT UNSIGNED mein badal rahe hain
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `proof_media_id` BIGINT UNSIGNED NULL");
    }
};