<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('ledgers', function (Blueprint $table) {
        $table->unsignedBigInteger('phase_id')->nullable()->after('status');
        $table->unsignedBigInteger('company_id')->nullable()->after('phase_id');
    });
}

public function down(): void
{
    Schema::table('ledgers', function (Blueprint $table) {
        $table->dropColumn(['phase_id', 'company_id']);
    });
}
};
