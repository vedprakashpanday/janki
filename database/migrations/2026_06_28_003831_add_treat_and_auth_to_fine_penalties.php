<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up()
    {
        Schema::table('fine_penalties', function (Blueprint $table) {
            
            // Check if treat_as does NOT exist, then add it
            if (!Schema::hasColumn('fine_penalties', 'treat_as')) {
                $table->enum('treat_as', ['warning', 'final', 'apply'])->default('apply')->after('description');
            }
            
            // Add Authorization columns if they do not exist
            if (!Schema::hasColumn('fine_penalties', 'auth_name')) {
                $table->string('auth_name')->nullable()->after('treat_as');
            }
            if (!Schema::hasColumn('fine_penalties', 'auth_code')) {
                $table->string('auth_code')->nullable()->after('auth_name');
            }
            if (!Schema::hasColumn('fine_penalties', 'auth_date')) {
                $table->date('auth_date')->nullable()->after('auth_code');
            }
        });
    }

    public function down()
    {
        Schema::table('fine_penalties', function (Blueprint $table) {
            // Drop columns safely
            $columnsToDrop = [];
            if (Schema::hasColumn('fine_penalties', 'treat_as')) $columnsToDrop[] = 'treat_as';
            if (Schema::hasColumn('fine_penalties', 'auth_name')) $columnsToDrop[] = 'auth_name';
            if (Schema::hasColumn('fine_penalties', 'auth_code')) $columnsToDrop[] = 'auth_code';
            if (Schema::hasColumn('fine_penalties', 'auth_date')) $columnsToDrop[] = 'auth_date';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
