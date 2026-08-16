<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            
            // Relational Links
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // Nullable for Head Office
            
            // Stock Details
            $table->string('item_name');
            $table->string('category')->nullable(); // e.g., Electric, Furniture, IT
            $table->date('entry_date'); // Kab aaya
            $table->string('serial_number')->nullable(); // Kuch me number hota hai, kuch me nahi
            
            // Quantities & Pricing
            $table->decimal('price', 15, 2)->default(0.00);
            $table->integer('total_quantity')->default(1);
            $table->integer('lost_quantity')->default(0); // Kitna bhula gaya
            
            // Future linking
            $table->unsignedBigInteger('debit_voucher_id')->nullable();
            
            // Status Tracking
            $table->enum('status', ['pending', 'active', 'inactive', 'damaged'])->default('active');
            
            $table->timestamps();

            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};