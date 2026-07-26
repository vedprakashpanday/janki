<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_devices', function (Blueprint $table) {
            $table->id();
            // Member ID jo 'members' table ke 'member_id' string (jaise ABDPL-M/001) se link hogi
            $table->string('member_id'); 
            
            // Frontend se aane wala unique token (Localstorage/Cookie me save hoga)
            $table->string('device_token')->unique(); 
            
            // Strict check: Primary ya Secondary
            $table->enum('device_type', ['primary', 'secondary']); 
            
            $table->string('device_name')->nullable(); // Jaise "Ritesh's iPhone 13"
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            
            // Admin yahan se block kar sakta hai
            $table->enum('status', ['active', 'blocked'])->default('active'); 
            $table->timestamps();

            // Ek member ke ek type ka ek hi active device ho sakta hai (Optional constraints)
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_devices');
    }
};