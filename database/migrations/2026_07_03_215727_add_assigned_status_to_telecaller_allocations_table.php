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
            $table->string('assigned_status', 255)
                  ->nullable()
                  ->default('Fresh / Pending')
                  ->after('call_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn('assigned_status');
        });
    }
};