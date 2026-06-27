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
            // assigned_telecaller column ke theek baad add kar rahe hain
            $table->string('called_by')->nullable()->after('assigned_telecaller');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn('called_by');
        });
    }
};