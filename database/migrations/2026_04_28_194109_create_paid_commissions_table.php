<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paid_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_id')->constrained('debit_vouchers')->onDelete('cascade');
            $table->string('member_id');
            $table->decimal('total_commission_due', 15, 2);
            $table->decimal('advance_adjusted', 15, 2)->default(0.00);
            $table->decimal('net_commission_paid', 15, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_commissions');
    }
};