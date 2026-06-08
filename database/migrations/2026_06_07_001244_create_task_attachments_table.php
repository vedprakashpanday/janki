<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();

            // Uploader (Kisne ye file attach ki)
            $table->string('uploader_type');
            $table->unsignedBigInteger('uploader_id');
            $table->index(['uploader_type', 'uploader_id']);

            $table->string('file_name'); // Display name (e.g. "Receipt_12.jpg")
            $table->string('file_path'); // Storage path
            $table->string('file_type')->nullable(); // Extension (jpg, pdf, xlsx)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};