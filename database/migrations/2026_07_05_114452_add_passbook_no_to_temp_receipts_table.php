<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temp_receipts', function (Blueprint $table) {
            $table->string('passbook_no')->nullable()->after('phase_id');
        });
    }

    public function down(): void
    {
        Schema::table('temp_receipts', function (Blueprint $table) {
            $table->dropColumn('passbook_no');
        });
    }
};