<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_logins', function (Blueprint $table) {
            $table->id();
            // Employee ki asli ID (Jaise: ABA/BR/DBG1/001) yahan link hogi
            $table->string('user_id')->unique(); 
            $table->string('panel_assign')->default('Employee');
            $table->string('panel_id')->unique(); // Login ID
            $table->string('panel_password');
            $table->string('panel_otp')->nullable();
            $table->timestamp('otp_time_till')->nullable();
            
            // Device Fingerprinting Columns
            $table->text('primary_device')->nullable();
            $table->text('secondary_device')->nullable();
            $table->json('other_devices')->nullable(); // Unauthorized devices ka log
            
            // Time & Status Control
            $table->time('p_time_from')->default('09:00:00'); // Default 9 AM
            $table->time('p_time_to')->default('18:00:00');   // Default 6 PM
            $table->time('s_time_from')->nullable();
            $table->time('s_time_to')->nullable();
            $table->enum('p_status', ['allow', 'deny'])->default('allow');
            $table->enum('s_status', ['allow', 'deny'])->default('deny');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_logins');
    }
};