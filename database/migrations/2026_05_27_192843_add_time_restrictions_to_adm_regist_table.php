<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table ka naam aapki employee table se match kar lein (jaise 'employees' ya 'adm_regist')
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->date('access_start_date')->nullable()->after('password');
            $table->date('access_end_date')->nullable()->after('access_start_date');
            $table->time('daily_start_time')->nullable()->after('access_end_date');
            $table->time('daily_end_time')->nullable()->after('daily_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn(['access_start_date', 'access_end_date', 'daily_start_time', 'daily_end_time']);
        });
    }
};