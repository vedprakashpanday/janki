<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            // Address ke baad column add kar rahe hain, nullable rakhenge taaki purane data par error na aaye
            $table->string('preferred_location')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn('preferred_location');
        });
    }
};