<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. customers टेबल में d_o_l ऐड करना और customer_code को nullable बनाना
        Schema::table('customers', function (Blueprint $table) {
            $table->date('d_o_l')->nullable()->after('joining_date');
            
            // customer_code को nullable बना रहे हैं ताकि हम इसमें NULL भर सकें
            $table->string('customer_code')->nullable()->change();
        });

        // 2. customer_records टेबल में member_id ऐड करना
        Schema::table('customer_records', function (Blueprint $table) {
            $table->string('member_id')->nullable()->after('branch_id');
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('d_o_l');
            $table->string('customer_code')->nullable(false)->change(); // वापस NOT NULL
        });

        Schema::table('customer_records', function (Blueprint $table) {
            $table->dropColumn('member_id');
        });
    }
};