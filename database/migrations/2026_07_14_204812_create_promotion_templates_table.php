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
        Schema::create('promotion_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['employee', 'member'])->unique()->comment('One template for each type');
            $table->string('subject')->nullable();
            $table->longText('template_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_templates');
    }
};