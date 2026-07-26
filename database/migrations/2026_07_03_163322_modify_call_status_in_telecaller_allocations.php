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
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->string('call_status', 255)->nullable(false)->default('Pending status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            // Agar aapko purani state mein wapas jaana ho, toh yahan wo code likhein
            // Example: $table->string('call_status')->default(null)->change();
        });
    }
};