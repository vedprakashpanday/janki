<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            // Hum designation column ka naam purana hi rehne de sakte hain ya naya id bana sakte hain
            // Better hai naye structured IDs banayein:
            
            $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('designation_id')->nullable()->after('department_id');

            // Foreign keys (Optional but recommended for data integrity)
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['designation_id']);
            $table->dropColumn(['department_id', 'designation_id']);
        });
    }
};