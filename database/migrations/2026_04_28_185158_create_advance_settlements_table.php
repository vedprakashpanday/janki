<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advance_settlements', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys linking to debit_vouchers table
            $table->foreignId('advance_voucher_id')->constrained('debit_vouchers')->onDelete('cascade');
            $table->foreignId('recovery_voucher_id')->constrained('debit_vouchers')->onDelete('cascade');
            
            $table->decimal('amount_settled', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advance_settlements');
    }
};