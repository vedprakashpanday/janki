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
        Schema::table('phases', function (Blueprint $table) {
            // 'status' column add kar rahe hain jiska default value 'active' hoga
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active')->after('phase_name');
            // Agar aapke paas phase_name column nahi hai, toh aap `->after('id')` bhi use kar sakte hain.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};