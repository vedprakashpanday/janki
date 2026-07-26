<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            // Foreign key aur column drop karna
            $table->dropForeign('ledgers_branch_id_foreign');
            $table->dropIndex('ledgers_branch_id_foreign');
            $table->dropColumn('branch_id');

            // Status column update karna
            // Note: DB::statement ka use enum changes ke liye best hai
            DB::statement("ALTER TABLE ledgers MODIFY COLUMN status ENUM('Active', 'Inactive', 'Pending') NOT NULL DEFAULT 'Pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            // Wapas column add karna (Agar rollback zaroori ho)
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreign('branch_id')->references('id')->on('branches');
            
            // Status wapas change karna (Yahan apne purane status values likhein)
            DB::statement("ALTER TABLE ledgers MODIFY COLUMN status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active'");
        });
    }
};