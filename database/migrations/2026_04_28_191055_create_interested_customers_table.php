<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interested_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('assigned_telecaller')->nullable();
            
            $table->string('cust_name');
            $table->string('mobile');
            $table->string('alternate_no')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('date')->nullable();
            
            $table->string('interested_for')->nullable();
            $table->string('required_for')->nullable();
            $table->string('budget')->nullable();
            $table->string('reference')->nullable();
            $table->string('refer_by')->nullable();
            
            $table->string('status')->default('General');
            $table->date('followup_date')->nullable();
            $table->string('followup_month')->nullable();
            $table->text('remark')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interested_customers');
    }
};