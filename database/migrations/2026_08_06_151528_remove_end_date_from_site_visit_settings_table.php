<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('site_visit_settings', function (Blueprint $table) {
            $table->dropColumn('end_date');
        });
    }

    public function down()
    {
        Schema::table('site_visit_settings', function (Blueprint $table) {
            $table->date('end_date')->nullable();
        });
    }
};