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
        Schema::table('interested_customers', function (Blueprint $table) {
            // Company ID add kar rahe hain (Nullable for safety & Head Office logic)
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            
            // Entry Status for Approval Workflow
            $table->enum('entry_status', ['active', 'pending', 'inactive'])
                  ->default('pending') // By default pending me jayega, controller permission ke hisaab se active karega
                  ->after('status');

            // Foreign Key Constraint (Agar aapki companies table exist karti hai toh)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            // $table->dropForeign(['company_id']); // Agar upar foreign key enable ki hai toh isko uncomment karein
            $table->dropColumn(['company_id', 'entry_status']);
        });
    }
};