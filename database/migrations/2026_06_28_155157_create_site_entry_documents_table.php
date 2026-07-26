<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_entry_documents', function (Blueprint $table) {
            $table->id();
            // Polymorphic relation taki future me kahin bhi attach ho sake
            $table->morphs('documentable'); 
            
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('file_type', 50)->nullable();
            $table->string('file_size', 50)->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_entry_documents');
    }
};