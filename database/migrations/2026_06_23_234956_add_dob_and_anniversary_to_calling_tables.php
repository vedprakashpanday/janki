<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master Customer Table me add karein
        Schema::table('interested_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('interested_customers', 'dob')) {
                $table->date('dob')->nullable()->after('email');
            }
            if (!Schema::hasColumn('interested_customers', 'anniversary_date')) {
                $table->date('anniversary_date')->nullable()->after('dob');
            }
        });

        // Telecaller Allocation Log Table me add karein
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('telecaller_allocations', 'dob')) {
                $table->date('dob')->nullable()->after('budget');
            }
            if (!Schema::hasColumn('telecaller_allocations', 'anniversary_date')) {
                $table->date('anniversary_date')->nullable()->after('dob');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn(['dob', 'anniversary_date']);
        });

        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn(['dob', 'anniversary_date']);
        });
    }
};