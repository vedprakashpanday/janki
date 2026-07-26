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
        Schema::table('tbl_bank_details', function (Blueprint $table) {
            // Adding new columns with their specific positions
            $table->unsignedBigInteger('company_id')->default(1)->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            $table->string('created_by')->nullable()->after('ifsc_code');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_bank_details', function (Blueprint $table) {
            // Dropping the columns if we rollback the migration
            $table->dropColumn([
                'company_id',
                'branch_id',
                'created_by',
                'status'
            ]);
        });
    }
};