<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::create('term_conditions', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->enum('target_audience', ['employee', 'member', 'customer', 'vendor', 'general'])->default('general');
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
        Schema::dropIfExists('term_conditions');
    }
};
