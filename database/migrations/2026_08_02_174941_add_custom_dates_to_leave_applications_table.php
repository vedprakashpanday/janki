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
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->boolean('is_custom_date')->default(0)->after('application_type');
            $table->json('custom_dates')->nullable()->after('end_datetime');
            $table->json('approved_custom_dates')->nullable()->after('approved_end_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropColumn([
                'is_custom_date', 
                'custom_dates', 
                'approved_custom_dates'
            ]);
        });
    }
};