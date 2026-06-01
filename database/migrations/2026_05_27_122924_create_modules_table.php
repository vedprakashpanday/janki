<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable(); // For Sub-menus
            $table->string('module_name');
            $table->string('route')->nullable(); // URL path
            $table->string('icon')->nullable();  // FontAwesome icon class
            $table->string('permission_base')->nullable(); // Spatie base code (e.g., 'employee', 'company')
            $table->integer('sequence')->default(0); // Ordering ke liye
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Self-referencing foreign key for parent-child tracking
            $table->foreign('parent_id')->references('id')->on('modules')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};