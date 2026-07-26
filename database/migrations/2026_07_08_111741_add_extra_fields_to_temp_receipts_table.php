<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('temp_receipts', function (Blueprint $table) {
            // Customer Details
            $table->string('father_name')->nullable()->after('customer_name');
            $table->string('spouse_name')->nullable()->after('father_name');
            $table->string('customer_mobile')->nullable()->after('spouse_name');
            $table->text('address')->nullable()->after('customer_mobile');

            // Employee (Received By) Details
            $table->string('received_by_emp_code')->nullable()->after('auth_ceo_id');
            $table->string('received_by_department')->nullable()->after('received_by_emp_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temp_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'father_name',
                'spouse_name',
                'customer_mobile',
                'address',
                'received_by_emp_code',
                'received_by_department'
            ]);
        });
    }
};