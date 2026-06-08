<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Tasks Table
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title'); 
            $table->text('description')->nullable();
            $table->unsignedBigInteger('assigned_by'); // जिसने टास्क दिया (Admin/Manager ID)
            $table->morphs('assignable'); // यह assignable_type और assignable_id बनाएगा
            $table->integer('target_count')->default(1); 
            $table->integer('achieved_count')->default(0); 
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        // 2. Task Replies Table (बातचीत और रिमार्क के लिए)
        Schema::create('task_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('sender_type'); // किसने रिप्लाई किया (Model Path)
            $table->unsignedBigInteger('sender_id'); 
            $table->text('message');
            $table->boolean('is_remark')->default(false); // Admin का मैसेज true होगा
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_replies');
        Schema::dropIfExists('tasks');
    }
};