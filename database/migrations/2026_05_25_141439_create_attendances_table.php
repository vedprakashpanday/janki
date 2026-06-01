<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->string('user_id'); // Employee ID
            $table->date('date');
            
            // Attendance Status Flags (1 ya 0 rakhenge logic ke hisab se)
            $table->boolean('present')->default(0);
            $table->boolean('absent')->default(0);
            $table->boolean('half_day')->default(0);
            $table->boolean('leave')->default(0);
            $table->boolean('short_leave')->default(0);
            
            // Other Data
            $table->decimal('fine', 8, 2)->default(0.00);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->time('login_time')->nullable();
            $table->time('logout_time')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};