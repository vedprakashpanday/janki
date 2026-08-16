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
        Schema::table('interested_customers', function (Blueprint $table) {
            // Naya column add kar rahe hain, default value 0 ke sath
            $table->tinyInteger('is_assigned')->default(0)->after('status'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn('is_assigned');
        });
    }
};