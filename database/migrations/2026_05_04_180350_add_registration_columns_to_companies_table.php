<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // DB mein hum sabko nullable rakhenge taaki purane records pe error na aaye. 
            // Mandatory ka logic hum Controller (Validation) mein lagayenge.
            $table->string('cin_no')->nullable()->after('company_code');
            $table->string('iso_no')->nullable()->after('cin_no');
            $table->string('trademark')->nullable()->after('iso_no');
            $table->string('logo_reg_no')->nullable()->after('trademark');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['cin_no', 'iso_no', 'trademark', 'logo_reg_no']);
        });
    }
};