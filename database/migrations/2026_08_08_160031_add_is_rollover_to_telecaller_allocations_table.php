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
        // 1. Pehle 'is_rollover' column add karte hain
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->tinyInteger('is_rollover')->default(0)->after('call_status');
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Agar kabhi rollback karna pade, to column delete kar denge
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn('is_rollover');
        });
    }
};