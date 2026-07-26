<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_login_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('member_id');
            
            // Kaun se registered device se login hua
            $table->unsignedBigInteger('member_device_id'); 
            
            $table->timestamp('login_time');
            $table->timestamp('logout_time')->nullable();
            
            // IP aur Location Tracking
            $table->string('ip_address')->nullable();
            $table->string('login_lat')->nullable();
            $table->string('login_lng')->nullable();
            $table->string('logout_lat')->nullable();
            $table->string('logout_lng')->nullable();
            
            $table->timestamps();
            
            // Foreign Key Link (Optional but recommended)
            // $table->foreign('member_device_id')->references('id')->on('member_devices')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_login_sessions');
    }
};