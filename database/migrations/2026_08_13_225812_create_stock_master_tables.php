<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categories Table
        Schema::create('stock_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Electronics, Utensils
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Types Table (Dependent on Category)
        Schema::create('stock_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('stock_categories')->onDelete('cascade');
            $table->string('name'); // e.g., Smartphone, LED TV, Copper Plate
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 3. Brands Table
        Schema::create('stock_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Samsung, Milton, Apple
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_brands');
        Schema::dropIfExists('stock_types');
        Schema::dropIfExists('stock_categories');
    }
};