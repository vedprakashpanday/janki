<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('designation_name');
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');

            // Foreign Keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['company_id', 'branch_id']);
        });
    }
};