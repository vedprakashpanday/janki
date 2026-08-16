<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_signatory_mappings', function (Blueprint $table) {
            $table->id();
            
            // Module Type: 'debit_voucher' or 'receipt_voucher'
            $table->string('module', 50);
            
            // Hierarchy Links
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // Null means 'Head Office'
            $table->unsignedBigInteger('department_id')->nullable(); 
            
            // Kis role ke liye set kar rahe hain? ('prepared_by', 'approved_by', 'authorized_signatory')
            $table->string('signatory_type', 50);
            
            // Multi-select categories ('employee', 'director', 'ceo')
            $table->string('person_type', 50);
            
            // Exact ID of the person (member_id, director_id, or ceo_id)
            $table->string('person_id', 100);
            
            // Kisne ye setting banayi/update ki
            $table->unsignedBigInteger('created_by')->nullable();
            
            $table->timestamps();

            // Foreign keys for data integrity
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // Branch aur Department null ho sakte hain, isliye strict cascade lagane ki bajaye normal index chalega
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_signatory_mappings');
    }
};