<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fine_penalties', function (Blueprint $table) {
            // નया User Type कॉलम ऐड कर रहे हैं
            if (!Schema::hasColumn('fine_penalties', 'user_type')) {
                $table->enum('user_type', ['Employee', 'Member'])->default('Employee')->after('id');
            }
        });

        // ENUM को DECIMAL में बदलने के लिए Raw SQL सबसे सेफ है (Laravel के डिफॉल्ट एरर से बचने के लिए)
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `fine_days` DECIMAL(5,2) DEFAULT NULL");
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `penalty_days` DECIMAL(5,2) DEFAULT NULL");
    }

    public function down()
    {
        Schema::table('fine_penalties', function (Blueprint $table) {
            if (Schema::hasColumn('fine_penalties', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });

        // रोलबैक करने के लिए वापस ENUM
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `fine_days` ENUM('Quarter Day','Half Day','Full Day') DEFAULT NULL");
        DB::statement("ALTER TABLE `fine_penalties` MODIFY `penalty_days` ENUM('Quarter Day','Half Day','Full Day') DEFAULT NULL");
    }
};