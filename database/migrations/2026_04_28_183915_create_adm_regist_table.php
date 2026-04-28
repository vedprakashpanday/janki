<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('adm_regist', function (Blueprint $table) {
            $table->id();
            $table->string('member_id')->unique();
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Personal Details
            $table->string('full_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('father_spouse_name')->nullable();
            $table->string('gender')->nullable();
            $table->string('designation')->nullable();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->date('anniversary_date')->nullable();
            
            // Contact Details
            $table->string('contact_no')->nullable();
            $table->string('alternate_no')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            
            // Identity Details
            $table->string('aadhar_no')->nullable();
            $table->string('pan_no')->nullable();
            
            // Address Details
            $table->text('communication_address')->nullable();
            $table->string('city')->nullable();
            $table->string('pin_code')->nullable();
            $table->string('native_place')->nullable();
            
            // Other Info
            $table->string('marital_status')->nullable();
            $table->string('nationality')->default('Indian');
            $table->string('blood_group')->nullable();
            
            // Documents & PDFs
            $table->string('aadhar_pdf')->nullable();
            $table->string('pan_pdf')->nullable();
            $table->string('bank_passbook_pdf')->nullable();
            $table->string('driving_license_pdf')->nullable();
            $table->string('passport_pdf')->nullable();
            $table->string('tenth_pdf')->nullable();
            $table->string('twelfth_pdf')->nullable();
            $table->string('graduation_pdf')->nullable();
            $table->string('pg_pdf')->nullable();
            $table->string('other_pdf')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('signature_photo')->nullable();
            
            // Nominee Details
            $table->string('nominee_name')->nullable();
            $table->string('nominee_relation')->nullable();
            $table->string('nominee_so_do_wo')->nullable();
            $table->date('nominee_dob')->nullable();
            $table->string('nominee_mobile')->nullable();
            $table->string('nominee_alternate_mobile')->nullable();
            $table->string('nominee_email')->nullable();
            $table->string('nominee_aadhar')->nullable();
            $table->string('nominee_pan')->nullable();
            $table->text('nominee_address')->nullable();
            $table->string('nominee_pincode')->nullable();
            $table->string('nominee_state')->nullable();
            $table->string('nominee_district')->nullable();
            
            // Nominee Documents
            $table->string('nom_aadhar_pdf')->nullable();
            $table->string('nom_pan_pdf')->nullable();
            $table->string('nom_bank_passbook_pdf')->nullable();
            $table->string('nom_driving_license_pdf')->nullable();
            $table->string('nom_passport_pdf')->nullable();
            $table->string('nom_passport_photo')->nullable();
            $table->string('nom_tenth_pdf')->nullable();
            $table->string('nom_twelfth_pdf')->nullable();
            $table->string('nom_graduation_pdf')->nullable();
            $table->string('nom_pg_pdf')->nullable();
            $table->string('nom_other_pdf')->nullable();
            
            // Status & Leave
            $table->enum('emp_status', ['active', 'inactive'])->default('active');
            $table->date('d_o_l')->nullable();
            $table->text('d_remarks')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adm_regist');
    }
};