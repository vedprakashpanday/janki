<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action_name'); // e.g., 'Direct Entry', 'Print & Export'
            $table->string('action_slug'); // e.g., 'add_direct', 'print'
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_actions');
    }
};