<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            // Naya column jo record karega ki employee kis company me transfer hua hai
            $table->unsignedBigInteger('transferred_to_company')->nullable()->after('emp_status');
        });
    }

    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('transferred_to_company');
        });
    }
};