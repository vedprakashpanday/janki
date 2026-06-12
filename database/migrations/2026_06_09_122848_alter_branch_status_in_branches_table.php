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
        // Raw SQL statement taaki ENUM me 'pending' bina kisi validation issue ke add ho jaye
        DB::statement("ALTER TABLE branches MODIFY COLUMN branch_status ENUM('active', 'inactive', 'pending') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback karne par 'pending' wapas hat jayega
        // Note: Rollback karne se pehle ensure karein ki kisi row me 'pending' status na ho, nahi toh database error dega
        DB::statement("ALTER TABLE branches MODIFY COLUMN branch_status ENUM('active', 'inactive') DEFAULT 'active'");
    }
};