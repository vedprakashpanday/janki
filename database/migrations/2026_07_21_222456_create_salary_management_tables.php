<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Employee Loans Table
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('loan_code')->unique(); // EMP/LOAN/001
            $table->unsignedBigInteger('employee_id'); // Links to adm_regist
            $table->unsignedBigInteger('debit_voucher_id')->nullable(); // Links to debit_vouchers
            
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_amount', 10, 2);
            $table->enum('status', ['active', 'settled'])->default('active');
            
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('adm_regist')->onDelete('cascade');
            $table->foreign('debit_voucher_id')->references('id')->on('debit_vouchers')->onDelete('set null');
        });

        // 2. Salaries Table (Generated & Finalized Salaries)
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('employee_id');
            
            $table->string('salary_month'); // Format: YYYY-MM
            $table->decimal('base_salary', 10, 2);
            $table->decimal('per_day_salary', 10, 2);

            // Attendance Tracking
            $table->decimal('present_days', 5, 2)->default(0);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('half_days', 5, 2)->default(0);
            $table->decimal('paid_leaves', 5, 2)->default(0);
            $table->decimal('short_leaves', 5, 2)->default(0);
            $table->decimal('week_offs', 5, 2)->default(0);
            $table->decimal('holidays', 5, 2)->default(0);
            $table->decimal('extra_days', 5, 2)->default(0);
            $table->decimal('total_payable_days', 5, 2)->default(0);

            // Earnings
            $table->decimal('actual_salary', 10, 2)->default(0.00); // Days * Per Day Salary
            $table->decimal('travel_allowance_added', 10, 2)->default(0.00);
            
            // Future Modules (Default 0)
            $table->decimal('epf', 10, 2)->default(0.00);
            $table->decimal('hra', 10, 2)->default(0.00);
            $table->decimal('ha', 10, 2)->default(0.00);

            // Deductions
            $table->decimal('fine_deduction', 10, 2)->default(0.00);
            $table->decimal('loan_deduction', 10, 2)->default(0.00);

            // Final Payout
            $table->decimal('net_payable_salary', 10, 2)->default(0.00);

            // RBAC Status: pending (add_request), active (add_direct/appr), inactive (rej)
            $table->enum('status', ['pending', 'active', 'inactive', 'paid'])->default('pending');
            
            // Audit Trails
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('adm_regist')->onDelete('cascade');
        });

        // 3. Employee Loan Repayments Table (Ledger for Loans)
        Schema::create('employee_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_loan_id');
            $table->unsignedBigInteger('salary_id')->nullable(); // Jis salary se kata
            $table->string('salary_month'); // e.g., 2026-07
            $table->date('deduction_date');
            $table->decimal('amount_deducted', 10, 2);
            $table->timestamps();

            $table->foreign('employee_loan_id')->references('id')->on('employee_loans')->onDelete('cascade');
            $table->foreign('salary_id')->references('id')->on('salaries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_loan_repayments');
        Schema::dropIfExists('salaries');
        Schema::dropIfExists('employee_loans');
    }
};