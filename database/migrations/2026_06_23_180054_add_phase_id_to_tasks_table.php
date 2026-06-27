<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Task kis phase ke liye hai (Nullable isliye rakha hai kyunki ho sakta hai koi task general ho, calling ka na ho)
            $table->unsignedBigInteger('phase_id')->nullable()->after('tracking_module_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('phase_id');
        });
    }
};