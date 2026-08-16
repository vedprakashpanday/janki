<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Existing Stocks Table
        Schema::table('stocks', function (Blueprint $table) {
            // Drop old string category column
            if (Schema::hasColumn('stocks', 'category')) {
                $table->dropColumn('category'); 
            }

            // Add new Foreign Keys & Dates
            $table->unsignedBigInteger('category_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('type_id')->nullable()->after('category_id');
            $table->unsignedBigInteger('brand_id')->nullable()->after('type_id');
            $table->unsignedBigInteger('incharge_id')->nullable()->after('brand_id')->comment('Linked to adm_regist id');
            $table->date('purchase_date')->nullable()->after('entry_date');

            // Set constraints (Optional but recommended for data integrity)
            $table->foreign('category_id')->references('id')->on('stock_categories')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('stock_types')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('stock_brands')->onDelete('set null');
            // Not setting hard constraint on incharge_id in case adm_regist has different structure, but it will store the ID
        });

        // 2. Table to store selected options for a stock entry
        Schema::create('stock_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('stock_attributes')->onDelete('cascade');
            $table->foreignId('attribute_option_id')->constrained('stock_attribute_options')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_attribute_values');
        
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['type_id']);
            $table->dropForeign(['brand_id']);
            
            $table->dropColumn(['category_id', 'type_id', 'brand_id', 'incharge_id', 'purchase_date']);
            $table->string('category')->nullable()->after('item_name');
        });
    }
};