<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Update ENUM in incentives table (Adding 'calculated')
        DB::statement("ALTER TABLE incentives MODIFY COLUMN incentive_status ENUM('pending', 'active', 'calculated', 'rejected', 'hold') DEFAULT 'pending'");

        // 2. Add incentive_added column to salaries table
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('incentive_added', 10, 2)->default(0)->after('travel_allowance_added');
        });
    }

    public function down()
    {
        // Revert ENUM if needed (Removing 'calculated')
        DB::statement("ALTER TABLE incentives MODIFY COLUMN incentive_status ENUM('pending', 'active', 'rejected', 'hold') DEFAULT 'pending'");

        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn('incentive_added');
        });
    }
};