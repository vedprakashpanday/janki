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
        Schema::table('adm_regist', function (Blueprint $table) {
            // Salary ke liye decimal datatype best hota hai. 
            // Aap apni requirement ke hisaab se 'nullable()' ya 'default(0)' laga sakte hain.
            $table->decimal('payable_salary', 10, 2)->after('current_salary')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adm_regist', function (Blueprint $table) {
            $table->dropColumn('payable_salary');
        });
    }
};