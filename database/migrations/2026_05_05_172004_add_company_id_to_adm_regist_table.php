<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            // id column ke theek baad company_id add kar rahe hain
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};