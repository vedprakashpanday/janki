<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            $table->string('approver_role')->nullable()->after('approver_id');
        });
    }

    public function down(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            $table->dropColumn('approver_role');
        });
    }
};