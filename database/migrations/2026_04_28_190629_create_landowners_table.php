<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('landowners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('agent_id')->nullable();
            
            $table->string('land_owner_id')->unique();
            $table->string('land_id')->unique();
            $table->string('land_owner_name');
            $table->string('relation_name')->nullable();
            $table->date('lo_dob')->nullable();
            $table->string('lo_state')->nullable();
            $table->string('lo_district')->nullable();
            $table->string('lo_block')->nullable();
            $table->string('lo_panchayat')->nullable();
            $table->string('lo_village')->nullable();
            $table->string('lo_aadhar')->nullable();
            $table->string('lo_pan')->nullable();
            $table->text('address')->nullable();
            $table->string('mobile1')->nullable();
            $table->string('mobile2')->nullable();
            
            $table->date('agree_date')->nullable();
            $table->string('agree_dur')->nullable();
            $table->string('jamabandi')->nullable();
            $table->string('mauze_name')->nullable();
            $table->string('thana_no')->nullable();
            
            $table->json('khesra_no')->nullable();
            $table->json('khata')->nullable();
            $table->json('rakuwa')->nullable();
            $table->json('chauhaddi')->nullable();
            
            $table->decimal('rate_per_katha', 15, 2)->default(0.00);
            $table->decimal('total_land_value', 15, 2)->default(0.00);
            $table->decimal('agent_rate_per_katha', 15, 2)->default(0.00);
            $table->decimal('agent_total_land_value', 15, 2)->default(0.00);
            
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
            $table->string('aadhar_pdf')->nullable();
            $table->string('pan_pdf')->nullable();
            $table->string('bank_passbook_pdf')->nullable();
            $table->string('passport_photo')->nullable();
            $table->string('sign')->nullable();
            $table->string('khatiyaan_pdf')->nullable();
            $table->string('jamabandi_pdf')->nullable();
            $table->string('lo_agreement_pdf')->nullable();
            $table->string('registry_deed_pdf')->nullable();
            $table->string('link_deed_pdf')->nullable();
            $table->string('final_deed_pdf')->nullable();
            $table->string('other_pdf')->nullable();
            
            $table->string('nom_aadhar_pdf')->nullable();
            $table->string('nom_pan_pdf')->nullable();
            $table->string('nom_bank_passbook_pdf')->nullable();
            $table->string('nom_passport_pdf')->nullable();
            $table->string('nom_passport_photo')->nullable();
            $table->string('nom_other_pdf')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landowners');
    }
};