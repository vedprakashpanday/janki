<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_progress_logs', function (Blueprint $table) {
            $table->id();
            
            // Link to the main task
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();

            // Actor (Kisne ye update/reply diya - Admin, Director, Employee, ya Member)
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id');
            $table->index(['actor_type', 'actor_id']);

            // Action details
            $table->enum('log_type', ['progress_update', 'reply', 'remark']);
            $table->text('message_or_remark')->nullable();
            
            // Agar ye progress update hai, to kitni entry complete ki
            $table->integer('entries_completed')->default(0); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_progress_logs');
    }
};