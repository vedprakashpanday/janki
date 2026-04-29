<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debit_vouchers', function (Blueprint $table) {
            $table->id();
            
            // Ye 3 columns restricted hain (NOT NULL)
            $table->string('dv_no', 50)->unique();
            $table->date('voucher_date');
            $table->string('head_of_account', 255);
            
            // Baaki saare columns aapke SQL ke hisaab se (Sabhi Nullable)
            $table->string('paid_to', 255)->nullable();
            $table->string('project_name', 255)->nullable();
            $table->string('ledger', 255)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->text('amount_words')->nullable();
            $table->string('payment_mode', 50)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->date('bank_date')->nullable();
            $table->string('drawn_on', 255)->nullable();
            $table->string('account_no', 50)->nullable();
            $table->string('ifsc_code', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('pay_upi', 100)->nullable();
            $table->text('narration')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('pay_advance', 50)->nullable();
            $table->string('emp_id', 255)->nullable();
            $table->string('invoice_no', 255)->nullable();
            $table->tinyInteger('dv_adv')->default(0);
            $table->string('cleared_dv_no', 255)->nullable();
            $table->enum('adv_status', ['paid', 'unpaid'])->default('unpaid')->nullable();
            $table->decimal('adv_recovered', 10, 2)->default(0.00)->nullable();
            $table->decimal('gross_amount', 10, 2)->default(0.00)->nullable();
            $table->decimal('tds_amount', 10, 2)->default(0.00)->nullable();
            $table->string('branch_name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_vouchers');
    }
};