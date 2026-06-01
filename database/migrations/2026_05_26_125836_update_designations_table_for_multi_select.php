<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Pehle purane foreign keys aur columns hatayenge
            $table->dropForeign(['company_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['company_id', 'branch_id']);

            // Naye JSON columns add karenge array store karne ke liye
            $table->json('company_ids')->nullable()->after('designation_name');
            $table->json('branch_ids')->nullable()->after('company_ids');
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn(['company_ids', 'branch_ids']);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
        });
    }
};