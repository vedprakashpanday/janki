<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('incentives', function (Blueprint $table) {
            $table->id();
            
            // Core Relations
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable(); // Nullable for Head Office
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->string('emp_id',255)->comment('Mapped to adm_regist/employees');
            $table->unsignedBigInteger('incentive_type_id');
            
            // Calculation & Inputs
            $table->string('passbook_no')->nullable()->comment('For future unit registration integration');
            $table->decimal('net_amount', 15, 2)->default(0)->comment('Future auto-fetch amount');
            
            // Logic Fields
            $table->enum('calc_type', ['percentage', 'amount'])->default('amount');
            $table->enum('dist_type', ['each', 'all'])->default('each');
            $table->decimal('value', 15, 2)->default(0)->comment('User entered % or Amount');
            
            // Final Computed Amount for this specific employee row
            $table->decimal('calculated_amount', 15, 2)->default(0)->comment('Final incentive amount for this employee');

            // Part Payments & Finance Tracking
            $table->string('dv_no')->nullable()->comment('Linked Debit Voucher Number');
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->decimal('left', 15, 2)->default(0);
            $table->decimal('total_left', 15, 2)->default(0);
            
            // Statuses (Maker/Checker)
            $table->enum('incentive_status', ['pending', 'active', 'rejected', 'paid'])->default('pending');
            
            // Tracking
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Temporary delete ke liye zaroori
        });
    }

    public function down()
    {
        Schema::dropIfExists('incentives');
    }
};