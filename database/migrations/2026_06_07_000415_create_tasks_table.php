<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            
            // Scope Columns (Analytics aur access control ke liye)
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();

            // Assigner (Jisne task diya - Admin/Director/Employee)
            $table->string('assigner_type');
            $table->unsignedBigInteger('assigner_id');
            $table->index(['assigner_type', 'assigner_id']); // Fast query ke liye index

            // Assignee (Jisko task mila - Employee/Member)
            $table->string('assignee_type');
            $table->unsignedBigInteger('assignee_id');
            $table->index(['assignee_type', 'assignee_id']);

            // Dynamic Tracking Link
            $table->foreignId('tracking_module_id')->nullable()->constrained('task_tracking_modules')->nullOnDelete();

            // Task Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            $table->dateTime('due_datetime')->nullable();

            // Tracking Counts
            $table->integer('target_count')->default(0); // Jaise: 50 entry karni hai
            $table->integer('achieved_count')->default(0); // Kitni ho gayi (Auto update or manual)

            $table->enum('status', ['Pending', 'In-Progress', 'Under Review', 'Completed', 'Cancelled'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};