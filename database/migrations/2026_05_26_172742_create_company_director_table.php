<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_director', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('director_id');
            
            // Role field (e.g., 'Director', 'MD', 'CEO')
            $table->string('role')->default('Director');
            
            // Relations
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('director_id')->references('id')->on('directors')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_director');
    }
};