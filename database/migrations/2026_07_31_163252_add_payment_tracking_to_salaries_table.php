<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('net_payable_salary');
            $table->decimal('left_amount', 10, 2)->default(0)->after('paid_amount');
            $table->enum('salary_payment_type', ['none', 'part', 'full'])->default('none')->after('left_amount');
            $table->string('dv_no')->nullable()->after('salary_payment_type');
        });
    }

    public function down()
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'left_amount', 'salary_payment_type', 'dv_no']);
        });
    }
};