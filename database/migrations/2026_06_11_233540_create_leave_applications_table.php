<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            
            // 🏢 Hierarchy Tracking
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade'); // Nullable for HO
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('cascade');
            
            // 👤 User Identification (Employee or Member)
            $table->string('user_type')->comment('employee, member');
            $table->unsignedBigInteger('user_id'); // ID of the employee or member
            
            // 📝 Application Details
            $table->enum('application_type', ['Leave', 'Short Leave', 'Other']);
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->decimal('duration', 8, 2)->nullable()->comment('Calculated Days or Hours');
            
            // ✍️ Reason (Min 300 chars logic will be in validation)
            $table->text('reason');
            
            // 🚦 Status & Approvals
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('approved_duration', 8, 2)->nullable()->comment('Final approved days/hours');
            $table->text('remarks')->nullable()->comment('Visible to employee, added by approver');
            
            // 🧑‍⚖️ Action Taken By
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Optional: Agar deleted records ka backup rakhna ho
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_applications');
    }
};