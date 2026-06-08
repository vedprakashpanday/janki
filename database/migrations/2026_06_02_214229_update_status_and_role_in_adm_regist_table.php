<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Status ENUM me naye options add kar rahe hain (Raw SQL kyunki Laravel Enum modify me dikkat karta hai)
        DB::statement("ALTER TABLE adm_regist MODIFY COLUMN emp_status ENUM('active', 'inactive', 'pending', 'transferred', 'terminated', 'resigned') DEFAULT 'active'");

        // 2. Role column add kar rahe hain
        Schema::table('adm_regist', function (Blueprint $table) {
            if (!Schema::hasColumn('adm_regist', 'role')) {
                $table->string('role', 50)->default('employee')->after('designation_id');
            }
        });
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE adm_regist MODIFY COLUMN emp_status ENUM('active', 'inactive') DEFAULT 'active'");
        
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};