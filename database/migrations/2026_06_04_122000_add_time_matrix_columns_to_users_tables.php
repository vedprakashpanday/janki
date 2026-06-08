<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tables = ['adm_regist', 'super_admins', 'directors', 'members']; 

        foreach ($tables as $table) {
            // Check karte hain ki table database mein hai bhi ya nahi
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table_blueprint) use ($table) {
                    
                    // Har column ke liye check lagaya hai
                    if (!Schema::hasColumn($table, 'access_start_date')) {
                        $table_blueprint->date('access_start_date')->nullable();
                    }
                    if (!Schema::hasColumn($table, 'access_end_date')) {
                        $table_blueprint->date('access_end_date')->nullable();
                    }
                    if (!Schema::hasColumn($table, 'daily_start_time')) {
                        $table_blueprint->time('daily_start_time')->nullable();
                    }
                    if (!Schema::hasColumn($table, 'daily_end_time')) {
                        $table_blueprint->time('daily_end_time')->nullable();
                    }
                    
                });
            }
        }
    }

    public function down()
    {
        $tables = ['adm_regist', 'super_admins', 'directors', 'members'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table_blueprint) use ($table) {
                    if (Schema::hasColumn($table, 'access_start_date')) {
                        $table_blueprint->dropColumn('access_start_date');
                    }
                    if (Schema::hasColumn($table, 'access_end_date')) {
                        $table_blueprint->dropColumn('access_end_date');
                    }
                    if (Schema::hasColumn($table, 'daily_start_time')) {
                        $table_blueprint->dropColumn('daily_start_time');
                    }
                    if (Schema::hasColumn($table, 'daily_end_time')) {
                        $table_blueprint->dropColumn('daily_end_time');
                    }
                });
            }
        }
    }
};