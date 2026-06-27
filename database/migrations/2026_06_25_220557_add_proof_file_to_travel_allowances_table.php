<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            // Proof file column add kar rahe hain (remarks ke baad)
            $table->string('proof_file')->nullable()->after('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            $table->dropColumn('proof_file');
        });
    }
};