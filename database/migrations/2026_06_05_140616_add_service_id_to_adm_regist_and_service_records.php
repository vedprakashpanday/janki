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
        // 1. adm_regist table me service_id column add karna
        if (Schema::hasTable('adm_regist')) {
            Schema::table('adm_regist', function (Blueprint $table) {
                if (!Schema::hasColumn('adm_regist', 'service_id')) {
                    $table->string('service_id')->nullable()->after('member_id');
                }
            });
        }

        // 2. service_records table me service_id column add karna
        if (Schema::hasTable('service_records')) {
            Schema::table('service_records', function (Blueprint $table) {
                if (!Schema::hasColumn('service_records', 'service_id')) {
                    $table->string('service_id')->nullable()->after('member_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback ke samay dono tables se service_id column hatana
        if (Schema::hasTable('adm_regist')) {
            Schema::table('adm_regist', function (Blueprint $table) {
                if (Schema::hasColumn('adm_regist', 'service_id')) {
                    $table->dropColumn('service_id');
                }
            });
        }

        if (Schema::hasTable('service_records')) {
            Schema::table('service_records', function (Blueprint $table) {
                if (Schema::hasColumn('service_records', 'service_id')) {
                    $table->dropColumn('service_id');
                }
            });
        }
    }
};