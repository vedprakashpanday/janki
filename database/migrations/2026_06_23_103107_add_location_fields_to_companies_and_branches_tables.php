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
        // Companies table me columns add kar rahe hain
        Schema::table('companies', function (Blueprint $table) {
            $table->text('map_url')->nullable()->after('address')->comment('Stores raw Google Map Link or iframe');
            $table->decimal('latitude', 10, 8)->nullable()->after('map_url');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        // Branches table me columns add kar rahe hain
        Schema::table('branches', function (Blueprint $table) {
            $table->text('map_url')->nullable()->after('branch_location')->comment('Stores raw Google Map Link or iframe');
            $table->decimal('latitude', 10, 8)->nullable()->after('map_url');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['map_url', 'latitude', 'longitude']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['map_url', 'latitude', 'longitude']);
        });
    }
};