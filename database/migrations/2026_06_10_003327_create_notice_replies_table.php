<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notice_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained('notices')->onDelete('cascade');
            $table->string('sender_type'); // 'admin', 'employee', 'member', 'customer'
            $table->string('sender_id'); // Jisne reply kiya uski ID (EMP001, Admin ID, etc.)
            $table->string('sender_name')->nullable(); // Reply karne wale ka naam
            $table->longText('reply_text'); // Reply ka content
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notice_replies');
    }
};