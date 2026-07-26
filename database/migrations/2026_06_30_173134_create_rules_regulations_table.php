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
        Schema::create('rules_regulations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            // Yahan sirf employee aur member allow kiya hai
            $table->enum('target_audience', ['employee', 'member'])->default('employee');
            $table->longText('content');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rules_regulations');
    }
};