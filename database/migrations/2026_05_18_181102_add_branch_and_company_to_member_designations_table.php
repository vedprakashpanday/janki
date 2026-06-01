<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('member_designations', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('company_id');
            
            // Dhyan dein: Pehle designation_code aur name globally unique the, 
            // Ab humein branch-wise unique karna hai, isliye purane unique index hatane pad sakte hain.
            // Agar pehle unique array error de, toh in lines ko uncomment kijiyega:
            // $table->dropUnique(['designation_code']);
            // $table->dropUnique(['designation_name']);
        });
    }

    public function down(): void
    {
        Schema::table('member_designations', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'branch_id']);
        });
    }
};