<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Purane columns drop kar rahe hain
            $table->dropColumn(['company_ids', 'branch_ids']);
            
            // Naya department_id column add kar rahe hain
            $table->unsignedBigInteger('department_id')->nullable()->after('designation_name');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            
            $table->json('company_ids')->nullable();
            $table->json('branch_ids')->nullable();
        });
    }
};