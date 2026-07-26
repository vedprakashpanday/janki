<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('service_records', function (Blueprint $table) {
            // promotion_date column joining_date ke baad add kar rahe hain
            $table->date('promotion_date')->nullable()->after('joining_date');
        });
    }

    public function down()
    {
        Schema::table('service_records', function (Blueprint $table) {
            $table->dropColumn('promotion_date');
        });
    }
};