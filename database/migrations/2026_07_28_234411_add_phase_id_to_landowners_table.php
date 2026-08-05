<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('landowners', function (Blueprint $table) {
            // branch_id ke baad phase_id add kar rahe hain
            $table->unsignedBigInteger('phase_id')->nullable()->after('branch_id');
        });
    }

    public function down()
    {
        Schema::table('landowners', function (Blueprint $table) {
            $table->dropColumn('phase_id');
        });
    }
};