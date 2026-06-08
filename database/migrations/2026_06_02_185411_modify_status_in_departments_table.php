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
        // Enum list mein 'pending', 'approved', 'rejected' add kar rahe hain
        DB::statement("ALTER TABLE departments MODIFY COLUMN status ENUM('active', 'inactive', 'pending', 'approved', 'rejected') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            //
        });
    }
};
