<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up()
    {
        Schema::create('property_emi_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('phase_id');
            $table->string('plan_name'); 
            $table->integer('emi_tenure')->default(0); 
            
            // Naye fields
            $table->decimal('rate_discount_per_sqft', 10, 2)->default(0); 
            $table->decimal('downpayment_percentage', 5, 2); 
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_emi_plans');
    }
};