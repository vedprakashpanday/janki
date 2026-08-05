<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('debit_vouchers', function (Blueprint $table) {
        if (!Schema::hasColumn('debit_vouchers', 'sender_bank')) {
            $table->string('sender_bank', 255)->nullable()->after('drawn_on');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            //
        });
    }
};
