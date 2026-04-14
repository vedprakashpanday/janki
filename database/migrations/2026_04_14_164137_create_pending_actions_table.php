<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::create('pending_actions', function (Blueprint $table) {
        $table->id();
        $table->string('action_type'); // Jaise: 'admin_login'
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->json('payload')->nullable(); // Isme hum Reverb ka session_id save karenge
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_actions');
    }
};
