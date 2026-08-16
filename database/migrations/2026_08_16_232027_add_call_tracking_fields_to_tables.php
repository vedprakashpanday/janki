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
        // 1. interested_customers table me 'state' add karna
        Schema::table('interested_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('interested_customers', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
        });

        // 2. telecaller_allocations table me required columns add karna
        Schema::table('telecaller_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('telecaller_allocations', 'state')) {
                $table->string('state')->nullable()->after('remark');
            }
            if (!Schema::hasColumn('telecaller_allocations', 'calling_time')) {
                $table->time('calling_time')->nullable()->after('state');
            }
            if (!Schema::hasColumn('telecaller_allocations', 'calling_duration')) {
                $table->integer('calling_duration')->nullable()->comment('Duration in minutes')->after('calling_time');
            }
            
            // 🔥 NAYA: Late tracking ke liye smart columns (Step 4 me kaam aayenge)
            if (!Schema::hasColumn('telecaller_allocations', 'is_late')) {
                $table->boolean('is_late')->default(false)->after('calling_duration');
            }
            if (!Schema::hasColumn('telecaller_allocations', 'late_by_minutes')) {
                $table->integer('late_by_minutes')->nullable()->comment('Kitne minute late call ki')->after('is_late');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn(['state']);
        });

        Schema::table('telecaller_allocations', function (Blueprint $table) {
            $table->dropColumn([
                'state', 
                'calling_time', 
                'calling_duration', 
                'is_late', 
                'late_by_minutes'
            ]);
        });
    }
};