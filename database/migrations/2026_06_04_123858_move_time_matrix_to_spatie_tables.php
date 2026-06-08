<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Spatie Pivot Tables mein columns add karein (Har specific role/permission par rule lagega)
        $spatieTables = ['model_has_permissions', 'model_has_roles'];
        foreach ($spatieTables as $table) {
            Schema::table($table, function (Blueprint $table_blueprint) {
                $table_blueprint->date('access_start_date')->nullable();
                $table_blueprint->date('access_end_date')->nullable();
                $table_blueprint->time('daily_start_time')->nullable();
                $table_blueprint->time('daily_end_time')->nullable();
            });
        }

        // 2. User tables se purane (Global) columns hata dein (Taaki poora user block na ho)
        $userTables = ['adm_regist', 'super_admins', 'directors', 'members'];
        foreach ($userTables as $userTable) {
            if (Schema::hasTable($userTable)) {
                Schema::table($userTable, function (Blueprint $table_blueprint) use ($userTable) {
                    if (Schema::hasColumn($userTable, 'access_start_date')) $table_blueprint->dropColumn('access_start_date');
                    if (Schema::hasColumn($userTable, 'access_end_date')) $table_blueprint->dropColumn('access_end_date');
                    if (Schema::hasColumn($userTable, 'daily_start_time')) $table_blueprint->dropColumn('daily_start_time');
                    if (Schema::hasColumn($userTable, 'daily_end_time')) $table_blueprint->dropColumn('daily_end_time');
                });
            }
        }
    }

    public function down()
    {
        // Rollback setup (Optional but good practice)
    }
};