<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // Naya column bank branch ke liye
            if (!Schema::hasColumn('debit_vouchers', 'bank_branch')) {
                $table->string('bank_branch', 255)->nullable()->after('bank_name');
            }
            
            // BigInt ko Varchar me convert karna
            $table->string('approved_by', 255)->nullable()->change();
            $table->string('authorized_signatory', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->dropColumn('bank_branch');
        });
    }
};