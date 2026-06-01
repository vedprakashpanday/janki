<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_logins', function (Blueprint $table) {
            $table->json('blocked_devices')->nullable()->after('other_devices');
        });
    }

    public function down(): void
    {
        Schema::table('employee_logins', function (Blueprint $table) {
            $table->dropColumn('blocked_devices');
        });
    }
};