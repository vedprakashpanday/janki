<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Designations table ke enum mein 'pending' add kar rahe hain, jaisa departments mein kiya tha
        DB::statement("ALTER TABLE designations MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Revert fallback if needed
            DB::statement("ALTER TABLE designations MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
        });
    }
};