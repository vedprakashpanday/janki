<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Auto Task Settings table mein add karein
        Schema::table('auto_task_settings', function (Blueprint $table) {
            $table->integer('provider_percent')->default(50)->after('provider_id');
        });

        // Manual Tasks table mein add karein
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('provider_percent')->default(50)->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('auto_task_settings', function (Blueprint $table) {
            $table->dropColumn('provider_percent');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('provider_percent');
        });
    }
};