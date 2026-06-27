<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('fine_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('designation_id')->nullable()->constrained();
            $table->unsignedBigInteger('employee_id'); // adm_regist table reference
            
            $table->integer('fine_rupees')->nullable();
            $table->enum('fine_days', ['Quarter Day', 'Half Day', 'Full Day'])->nullable();
            
            $table->integer('penalty_rupees')->nullable();
            $table->enum('penalty_days', ['Quarter Day', 'Half Day', 'Full Day'])->nullable();
            
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('status')->default('Pending'); // Pending, Approved, Rejected
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fine_penalties');
    }
};