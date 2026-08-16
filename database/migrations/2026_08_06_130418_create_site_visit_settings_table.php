<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            // Cascading Foreign Keys
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // Null for Head Office
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->unsignedBigInteger('employee_id'); // Using adm_regist/Employee model
            
            // Core Data
            $table->unsignedBigInteger('phase_id');
            $table->string('customer_contact_number', 20);
            $table->date('visit_date');
            $table->text('description')->nullable();
            
            // Approval & RBAC tracking
            $table->text('remarks')->nullable(); // Admin remarks on approve/reject
            $table->enum('status', ['pending', 'active', 'inactive', 'rejected'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin who approved
            
            $table->timestamps();
            
            // Foreign Key Constraints (Optional but recommended for data integrity)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('employee_id')->references('id')->on('adm_regist')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_visits');
    }
};