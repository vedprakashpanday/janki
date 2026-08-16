<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // Hum ise status ke theek baad add kar rahe hain
            $table->text('checker_remarks')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->dropColumn('checker_remarks');
        });
    }
};