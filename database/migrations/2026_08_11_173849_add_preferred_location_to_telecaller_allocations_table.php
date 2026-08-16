<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            // Remark ke baad column add kar rahe hain
            $table->string('preferred_location')->nullable()->after('remark');
        });
    }

    public function down(): void
    {
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn('preferred_location');
        });
    }
};