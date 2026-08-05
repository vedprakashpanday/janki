<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('refer_by');
            $table->string('provider_id')->nullable()->after('provider_name');
        });
    }

    public function down()
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn(['provider_name', 'provider_id']);
        });
    }
};