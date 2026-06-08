<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('adm_regist id');
            $table->string('member_id')->comment('Generated Employee ID');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->date('joining_date')->nullable();
            $table->date('date_of_leaving')->nullable();
            $table->string('role', 50)->default('employee');
            $table->string('status', 50)->nullable();
            $table->timestamps();

            // Foreign Key Constraints (Optional but good for data integrity)
            $table->foreign('user_id')->references('id')->on('adm_regist')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_records');
    }
};