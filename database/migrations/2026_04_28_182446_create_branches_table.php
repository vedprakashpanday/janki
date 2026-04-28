<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_id')->unique();
            $table->string('branch_name');
            $table->string('branch_state')->nullable();
            $table->string('branch_district')->nullable();
            $table->date('opening_date')->nullable();
            $table->text('branch_location')->nullable();
            $table->text('branch_map')->nullable();
            $table->enum('branch_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};