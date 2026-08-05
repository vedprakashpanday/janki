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
        // Auto Task Settings table me add karein
        Schema::table('auto_task_settings', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->after('phase_id');
        });

        // Manual Tasks table me add karein
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('provider_id')->nullable()->after('phase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_task_settings', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('provider_id');
        });
    }
};