<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_allowances', function (Blueprint $table) {
            $table->id();
            
            // Relational IDs
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->unsignedBigInteger('employee_id'); // Referencing adm_regist.id
            
            // Form Fields (From Image)
            $table->date('ta_date');
            $table->string('vehicle_no')->nullable();
            $table->string('purpose')->nullable();
            $table->string('destination')->nullable();
            $table->string('distance_km')->nullable();
            $table->string('in_time')->nullable(); // Time as string directly from input
            $table->string('out_time')->nullable();
            $table->string('fuel_litre')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            
            // Workflow & Approval Fields
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approver_id')->nullable(); // Employee ID who approved/rejected
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            
            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('adm_regist')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('adm_regist')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_allowances');
    }
};