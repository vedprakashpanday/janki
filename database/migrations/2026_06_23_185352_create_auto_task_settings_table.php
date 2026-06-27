<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_task_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Jisko auto-task dena hai (Employee ya Member)
            $table->string('assignee_type');
            $table->unsignedBigInteger('assignee_id');
            
            // Task ki Details
            $table->string('title_template')->default('Daily Telecalling Target');
            $table->text('description_template')->nullable();
            $table->foreignId('tracking_module_id')->nullable()->constrained('task_tracking_modules')->nullOnDelete();
            $table->unsignedBigInteger('phase_id')->nullable(); // Kis phase ke liye
            
            $table->integer('daily_target_count')->default(0); // Daily kitna dena hai (e.g., 600)
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium');
            
            // Rollover feature (Kal ka pending aaj jodna hai ya nahi)
            $table->boolean('carry_forward_pending')->default(true); 
            
            $table->time('run_time')->default('09:00:00'); // Kitne baje assign karna hai
            $table->boolean('is_active')->default(true);
            
            $table->unsignedBigInteger('created_by'); // Jis admin/manager ne rule banaya
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_task_settings');
    }
};