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
        // 🔥 Ye line purane table ko delete kar degi agar wo exist karta hai
        Schema::dropIfExists('site_vehicle_trips');

        // Naya table create hoga aapke updated schema ke sath
        Schema::create('site_vehicle_trips', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('phase_name')->nullable(); // 🔥 Changed to string
            $table->string('slip_type'); 
            $table->string('slip_number')->unique(); 
            $table->date('trip_date');
            
            // Trip Details
            $table->string('vehicle_number');
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('arrival_image')->nullable(); // 🔥 Added Image support
            $table->string('departure_image')->nullable();
            
            // Signatures
            $table->unsignedBigInteger('site_supervisor_id')->nullable();
            $table->unsignedBigInteger('project_manager_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_vehicle_trips');
    }
};
