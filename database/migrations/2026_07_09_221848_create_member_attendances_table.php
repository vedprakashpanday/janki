<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->date('date'); // Attendance ki date
            
            // Attendance Status: present, absent, leave, sl (short leave)
            $table->enum('status', ['present', 'absent', 'leave', 'sl', 'pending'])->default('pending');
            
            // Punch In Details
            $table->dateTime('punch_in_time')->nullable();
            $table->decimal('punch_in_latitude', 10, 8)->nullable();
            $table->decimal('punch_in_longitude', 11, 8)->nullable();
            
            // Punch Out Details
            $table->dateTime('punch_out_time')->nullable();
            $table->decimal('punch_out_latitude', 10, 8)->nullable();
            $table->decimal('punch_out_longitude', 11, 8)->nullable();
            
            // HR Correction ke liye
            $table->string('remarks')->nullable(); 
            $table->unsignedBigInteger('corrected_by')->nullable(); // Jis HR/Admin ne correct kiya uski ID

            $table->timestamps();

            // Fast searching ke liye index
            $table->index(['member_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_attendances');
    }
};