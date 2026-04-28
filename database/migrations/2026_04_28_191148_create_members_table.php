<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('member_id')->unique();
            $table->string('password');
            $table->string('sponsor_id')->nullable();
            $table->string('sponsor_name')->nullable();
            $table->string('member_name');
            $table->string('blood_group')->nullable();
            $table->string('so_do_name')->nullable();
            $table->string('parents_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->default('Indian');
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->date('date_of_anniversary')->nullable();
            
            // Contact
            $table->string('mobile');
            $table->string('alternate_mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('pan_number')->nullable();
            $table->string('aadhar_number')->nullable();
            $table->string('native_place')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode')->nullable();
            
            // Bank Details
            $table->string('account_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('account_type')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('ifsc_code')->nullable();
            
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
            
            // Documents
            $table->string('aadharcard')->nullable();
            $table->string('pancard')->nullable();
            $table->string('bankpassbook')->nullable();
            $table->string('drivinglicense')->nullable();
            $table->string('passport')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('sign')->nullable();
            $table->string('tenthmarksheet')->nullable();
            $table->string('twelvethmarksheet')->nullable();
            $table->string('graduationcertificate')->nullable();
            $table->string('pgcertificate')->nullable();
            $table->string('otherdoc')->nullable();
            
            $table->string('nom_aadharcard')->nullable();
            $table->string('nom_pancard')->nullable();
            $table->string('nom_bankpassbook')->nullable();
            $table->string('nom_drivinglicense')->nullable();
            $table->string('nom_passport')->nullable();
            $table->string('nom_passport_photo')->nullable();
            $table->string('nom_tenthmarksheet')->nullable();
            $table->string('nom_twelvethmarksheet')->nullable();
            $table->string('nom_graduationcertificate')->nullable();
            $table->string('nom_pgcertificate')->nullable();
            $table->string('nom_otherdoc')->nullable();
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};