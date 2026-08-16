<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('site_visits', function (Blueprint $table) {
            // visit_date ke theek baad visit_time add karega
            $table->time('visit_time')->nullable()->after('visit_date'); 
        });
    }

    public function down()
    {
        Schema::table('site_visits', function (Blueprint $table) {
            $table->dropColumn('visit_time');
        });
    }
};