<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Employee primary ID (adm_regist.id)
            $table->date('date'); // Jis date ka attendance correct kiya gaya hai
            $table->string('original_status')->nullable(); // P, A, HD, L, HO, WO
            $table->string('corrected_status'); // P, A, HD, L, etc.
            $table->text('reason'); // Reason for manual override
            $table->unsignedBigInteger('action_by'); // Kis Admin/CEO ne change kiya
            $table->timestamps();

            // Ek din mein ek employee ka ek hi correction record active rahega
            $table->unique(['user_id', 'date']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};