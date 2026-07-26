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
        // 1. Employee Table
        if (Schema::hasTable('adm_regist')) {
            Schema::table('adm_regist', function (Blueprint $table) {
                $table->decimal('current_salary', 12, 2)->nullable()->after('designation_id');
            });
        }

        // 2. Member Table
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->decimal('current_salary', 12, 2)->nullable()->after('designation_id');
            });
        }

        // 3. Employee Service Records Table
        if (Schema::hasTable('service_records')) {
            Schema::table('service_records', function (Blueprint $table) {
                $table->decimal('current_salary', 12, 2)->nullable()->after('designation_id');
            });
        }

        // 4. Member Service Records Table
        if (Schema::hasTable('member_service_records')) {
            Schema::table('member_service_records', function (Blueprint $table) {
                // Member service records me jahan proper lage, wahan add kar denge
                $table->decimal('current_salary', 12, 2)->nullable(); 
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('adm_regist')) {
            Schema::table('adm_regist', function (Blueprint $table) {
                $table->dropColumn('current_salary');
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn('current_salary');
            });
        }

        if (Schema::hasTable('service_records')) {
            Schema::table('service_records', function (Blueprint $table) {
                $table->dropColumn('current_salary');
            });
        }

        if (Schema::hasTable('member_service_records')) {
            Schema::table('member_service_records', function (Blueprint $table) {
                $table->dropColumn('current_salary');
            });
        }
    }
};