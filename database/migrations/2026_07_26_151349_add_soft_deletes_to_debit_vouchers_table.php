<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // Soft delete column add karne ke liye
            $table->softDeletes(); 
        });
    }

    public function down()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};