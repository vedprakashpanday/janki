<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Pehle Foreign Key constraint drop karein
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        // 2. Fir column type change karein VARCHAR(255) me
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->string('employee_id', 255)->change();
        });
    }

    public function down()
    {
        // Rollback ke liye wapas BIGINT aur FK constraint add karna
        Schema::table('employee_loans', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->change();
            $table->foreign('employee_id')->references('id')->on('adm_regist')->onDelete('cascade');
        });
    }
};