<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // Purana global unique index hata rahe hain
            $table->dropUnique('debit_vouchers_dv_no_unique');
        });
    }

    public function down()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->unique('dv_no');
        });
    }
};