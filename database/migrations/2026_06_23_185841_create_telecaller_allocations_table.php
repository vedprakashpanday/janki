<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telecaller_allocations', function (Blueprint $table) {
            $table->id();
            
            // Linking IDs
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->unsignedBigInteger('phase_id')->nullable();
            $table->unsignedBigInteger('customer_id'); // interested_customers table ka ID
            
            // Jisko call karne ko diya gaya hai (Employee/Member)
            $table->string('assignee_type');
            $table->unsignedBigInteger('assignee_id');
            
            // Call Tracking Status
            // (Aapke bataye gaye options: connected, not reachable, follow up, site visit schedule, site visit done, booking done, lost)
            $table->enum('call_status', [
                'Pending', 
                'Connected', 
                'Not Reachable', 
                'Follow Up', 
                'Site Visit Scheduled', 
                'Site Visit Done', 
                'Booking Done', 
                'Lost'
            ])->default('Pending');
            
            // Call Details (Jo Telecaller update karega)
            $table->string('interested_for')->nullable(); // Villa, Plot, Villa & Plot
            $table->string('budget')->nullable();
            $table->date('followup_date')->nullable();
            $table->string('followup_month')->nullable();
            $table->text('remark')->nullable();
            
            // Timestamps
            $table->timestamp('called_at')->nullable(); // Jab actual call hui
            $table->timestamps();

            // Indexes for fast querying (Overlap rokne ke liye)
            $table->index(['customer_id', 'phase_id']);
            $table->index(['assignee_type', 'assignee_id', 'call_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telecaller_allocations');
    }
};