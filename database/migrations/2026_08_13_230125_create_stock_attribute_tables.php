<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Attributes Master (e.g., RAM, Material, Capacity)
        Schema::create('stock_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Attribute Options (e.g., 4GB, Copper, 5 Liters)
        Schema::create('stock_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('stock_attributes')->onDelete('cascade');
            $table->string('value'); 
            $table->timestamps();
        });

        // 3. Category - Attribute Mapping (Electronics -> RAM, Storage)
        Schema::create('stock_category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('stock_categories')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('stock_attributes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_category_attributes');
        Schema::dropIfExists('stock_attribute_options');
        Schema::dropIfExists('stock_attributes');
    }
};