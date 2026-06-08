<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // 'branch_id' ya 'id' ke baad column add karein. Nullable isliye rakha taaki purane records error na dein
            $table->unsignedBigInteger('company_id')->nullable()->after('id'); 
        });
    }

    public function down()
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->dropColumn('company_id');
        });
    }
};