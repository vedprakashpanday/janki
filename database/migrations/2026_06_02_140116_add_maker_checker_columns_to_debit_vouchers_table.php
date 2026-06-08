<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            // Maker-Checker Columns
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('amount');
            $table->unsignedBigInteger('approved_by')->nullable()->after('status')->comment('ID of the user who approved');
            $table->unsignedBigInteger('authorized_signatory')->nullable()->after('approved_by')->comment('ID of the authorized person');
        });
    }

    public function down(): void
    {
        Schema::table('debit_vouchers', function (Blueprint $table) {
            $table->dropColumn(['status', 'approved_by', 'authorized_signatory']);
        });
    }
};
