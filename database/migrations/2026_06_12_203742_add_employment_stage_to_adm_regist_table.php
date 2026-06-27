<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            // emp_status ke theek baad naya column add kar rahe hain default 'On Board' ke sath
            $table->string('employment_stage', 255)->default('On Board')->after('emp_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('employment_stage');
        });
    }
};