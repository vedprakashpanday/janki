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
        $tables = ['members', 'adm_regist', 'agents', 'landowners', 'vendors'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Agar aap foreign key use kar rahe hain, toh unsignedBigInteger better hai
                $table->unsignedBigInteger('created_by')->nullable()->after('id');
                
                // Optional: Agar users table se relate karna hai toh ye line uncomment karein
                // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['members', 'adm_regist', 'agents', 'landowners', 'vendors'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Agar upar foreign key add ki thi, toh usko pehle drop karna hoga:
                // $table->dropForeign(['created_by']);
                
                $table->dropColumn('created_by');
            });
        }
    }
};