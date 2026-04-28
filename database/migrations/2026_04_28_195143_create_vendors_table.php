<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            
            $table->string('vendor_id')->unique();
            $table->string('password');
            $table->string('vendor_type')->nullable();
            $table->string('vendor_gstin')->nullable();
            
            // Personal Info
            $table->string('full_name');
            $table->string('father_spouse_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->default('Indian');
            $table->date('dob')->nullable();
            $table->date('anniversary_date')->nullable();
            
            // Contact Info
            $table->string('contact_no');
            $table->string('alternate_no')->nullable();
            $table->string('email')->nullable();
            $table->string('pan_no')->nullable();
            $table->string('aadhar_no')->nullable();
            
            // Address Info
            $table->string('native_place')->nullable();
            $table->text('communication_address')->nullable();
            $table->string('city')->nullable();
            $table->string('pin_code')->nullable();
            
            // Bank Details
            $table->string('account_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('account_type')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('ifsc_code')->nullable();
            
            // Nominee Info
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
            
            // Documents
            $table->string('aadhar_pdf')->nullable();
            $table->string('pan_pdf')->nullable();
            $table->string('bank_passbook_pdf')->nullable();
            $table->string('driving_license_pdf')->nullable();
            $table->string('passport_pdf')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('other_pdf')->nullable();
            
            // Nominee Documents
            $table->string('nom_aadhar_pdf')->nullable();
            $table->string('nom_pan_pdf')->nullable();
            $table->string('nom_bank_passbook_pdf')->nullable();
            $table->string('nom_driving_license_pdf')->nullable();
            $table->string('nom_passport_pdf')->nullable();
            $table->string('nom_passport_photo')->nullable();
            $table->string('nom_other_pdf')->nullable();
            
            // Status & Leave
            $table->enum('vendor_status', ['active', 'inactive'])->default('active');
            $table->date('d_o_l')->nullable();
            $table->text('leaving_remarks')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};