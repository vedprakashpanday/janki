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
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            // followup_date ke theek baad followup_time add kar rahe hain (nullable rakha hai default)
            $table->time('followup_time')->nullable()->after('followup_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn('followup_time');
        });
    }
};