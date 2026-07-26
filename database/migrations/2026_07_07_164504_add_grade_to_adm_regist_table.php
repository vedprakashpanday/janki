<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            // Hum isko string rakh rahe hain taaki future me Grade E, F wagera easily aa sake
            $table->string('grade')->nullable()->after('emp_status');
        });
    }

    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};