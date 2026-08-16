<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_signatory_hierarchies', function (Blueprint $table) {
            $table->id();
            
            // Module (Debit Voucher ya Receipt Voucher)
            $table->string('module', 50);
            
            // Scope / Filter parameters
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); 
            $table->unsignedBigInteger('department_id')->nullable(); 
            
            // 🔗 BASE PERSON (Jiske login karne par checking hogi)
            // Debit Voucher me ye 'prepared_by' hoga. Receipt me ye 'approved_by' hoga.
            $table->string('base_role', 50); 
            $table->string('base_person_id', 100); 
            
            // 🎯 TARGET PERSON (Jo dropdown me dikhega)
            // Debit Voucher me ye 'approved_by' ya 'authorized_signatory' hoga
            $table->string('target_role', 50); 
            $table->string('target_person_type', 50); // 'employee', 'director', 'ceo'
            $table->string('target_person_id', 100);
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign Key
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_signatory_hierarchies');
    }
};