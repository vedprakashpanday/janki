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
        Schema::table('task_progress_logs', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(0)->after('message_or_remark');
            $table->json('deleted_for')->nullable()->after('is_deleted');
            $table->boolean('is_edited')->default(0)->after('deleted_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_progress_logs', function (Blueprint $table) {
            $table->dropColumn(['is_deleted', 'deleted_for', 'is_edited']);
        });
    }
};