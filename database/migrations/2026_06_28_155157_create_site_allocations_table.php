<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_incharge_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            // Nullable rakha hai "Head Office" logic ke liye
            $table->unsignedBigInteger('branch_id')->nullable()->comment('Null means Head Office'); 
            $table->unsignedBigInteger('employee_id')->comment('Reference to adm_regist');
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // JSON fields for flexible Select2 Tags
            $table->json('incharge_types')->nullable()->comment('e.g., ["Site Supervisor", "Site Guard"]');
            $table->json('allowed_categories')->nullable()->comment('e.g., ["Labour", "Material", "Goods Carrier"]');
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Indexes for fast data retrieval
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('employee_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_incharge_allocations');
    }
};