<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('site_visits', function (Blueprint $table) {
        $table->string('customer_name')->nullable()->after('phase_id');
    });
}

public function down()
{
    Schema::table('site_visits', function (Blueprint $table) {
        $table->dropColumn('customer_name');
    });
}
};
