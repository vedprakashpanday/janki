<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notices', function (Blueprint $table) {
            // 1. Status column: active, inactive, pending
            $table->string('status')->default('active')->after('requires_reply'); 

            // 2. Hierarchical Target Matrix Columns
            $table->unsignedBigInteger('target_company_id')->nullable()->after('status');
            $table->unsignedBigInteger('target_branch_id')->nullable()->after('target_company_id');
            $table->unsignedBigInteger('target_department_id')->nullable()->after('target_branch_id');
            
            // 3. To track who requested or approved/rejected
            $table->string('action_taken_by')->nullable()->after('created_by'); 
        });
    }

    public function down()
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'status', 
                'target_company_id', 
                'target_branch_id', 
                'target_department_id',
                'action_taken_by'
            ]);
        });
    }
};