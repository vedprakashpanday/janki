<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debit_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('dv_no')->unique();
            $table->date('v_date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('ledger_id');
            
            $table->string('paid_to_type');
            $table->unsignedBigInteger('paid_to_id');
            $table->decimal('amount', 15, 2);
            
            // Advance & Recovery fields
            $table->boolean('is_advance_taken')->default(0);
            $table->decimal('adv_recovered_amount', 15, 2)->default(0.00);
            $table->enum('adv_status', ['none', 'pending', 'partially_paid', 'settled'])->default('none');
            
            // Payment details
            $table->string('payment_mode');
            $table->text('bank_details')->nullable();
            $table->string('ref_no')->nullable();
            $table->text('narration')->nullable();
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_vouchers');
    }
};