<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('temp_receipts', function (Blueprint $table) {
            $table->id();
            
            // 🏢 Company & Branch
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // Nullable for Head Office
            
            // 📄 Basic Receipt Info
            $table->date('receipt_date');
            $table->string('receipt_no')->unique();
            $table->string('project_name')->default('Janki Villa');
            $table->unsignedBigInteger('phase_id')->nullable(); 
            
            // 👤 Customer Info
            $table->string('customer_name');
            $table->string('customer_identification_no')->nullable();
            
            // 💳 Payment Details
            $table->enum('payment_mode', ['Cash', 'UPI', 'Cheque', 'NEFT', 'RTGS', 'Other']);
            $table->string('cheque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('date_of_cheque')->nullable();
            $table->string('utr_no')->nullable();
            $table->string('transaction_no')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('received_bank_name')->nullable();
            
            // 📝 Remarks
            $table->text('remarks')->nullable();
            
            // 💰 Dynamic Amount Details (JSON array to store multiple particulars)
            $table->json('amount_details')->nullable(); 
            
            // 🏠 Property Details
            $table->enum('property_type', ['Plot', 'Villa', 'Flat'])->nullable();
            $table->string('unit_no')->nullable();
            $table->decimal('area_sqft', 10, 2)->nullable();
            
            // 📊 Payment Summary
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            
            // ✍️ Signatures / Approvals
            $table->unsignedBigInteger('approved_by_emp_id')->nullable(); // Account department employee
            $table->unsignedBigInteger('auth_ceo_id')->nullable(); // SuperAdmin / CEO
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('temp_receipts');
    }
};