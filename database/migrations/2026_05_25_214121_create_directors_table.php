<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('directors', function (Blueprint $table) {
            $table->id();
            $table->string('director_id')->unique(); // ID Format: DIR-100123
            
            // 1. Personal Details
            $table->string('full_name');
            $table->string('father_spouse_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('designation')->default('Director'); // Auto Default Lock
            $table->enum('gender', ['Male', 'Female', 'Others'])->nullable();
            $table->enum('marital_status', ['Unmarried', 'Married'])->default('Unmarried');
            $table->date('anniversary_date')->nullable(); // Pre-included
            $table->date('dob')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('contact_no');
            $table->string('alternate_no')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('pan_no')->nullable();
            $table->string('aadhar_no'); 
            $table->string('native_place')->nullable();
            $table->text('communication_address')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            $table->string('password');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('date_of_leaving_death')->nullable();

            // 2. Bank Details
            $table->string('account_holder_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('account_type')->nullable(); 
            $table->string('bank_name')->nullable();
            $table->string('ifsc_code')->nullable();

            // 3. Nominee Details
            $table->string('nominee_name')->nullable();
            $table->string('nominee_relation')->nullable();
            $table->string('nominee_so_do_wo')->nullable();
            $table->date('nominee_dob')->nullable();
            $table->string('nominee_mobile')->nullable();
            $table->string('nominee_alt_mobile')->nullable();
            $table->string('nominee_email')->nullable();
            $table->string('nominee_aadhar')->nullable();
            $table->string('nominee_pan')->nullable();
            $table->text('nominee_address')->nullable();
            $table->string('nominee_pincode')->nullable();
            $table->string('nominee_state')->nullable();
            $table->string('nominee_district')->nullable();

            // 4. Director Documents (Paths)
            $table->string('aadhar_pdf')->nullable();
            $table->string('pan_pdf')->nullable();
            $table->string('bank_passbook_pdf')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('signature_photo')->nullable();
            $table->string('residential_proof_pdf')->nullable();
            $table->string('landmark_doc_pdf')->nullable();
            $table->string('other_doc_pdf')->nullable();

            // 5. Nominee Documents (Paths)
            $table->string('nom_aadhar_pdf')->nullable();
            $table->string('nom_pan_pdf')->nullable();
            $table->string('nom_bank_passbook_pdf')->nullable();
            $table->string('nom_passport_photo')->nullable();
            $table->string('nom_signature_photo')->nullable();
            $table->string('nom_residential_proof_pdf')->nullable();
            $table->string('nom_landmark_doc_pdf')->nullable();
            $table->string('nom_other_doc_pdf')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directors');
    }
};