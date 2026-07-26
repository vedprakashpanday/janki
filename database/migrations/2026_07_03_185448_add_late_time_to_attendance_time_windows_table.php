<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('attendance_time_windows', function (Blueprint $table) {
        // late_time column add karna aur login_end ke baad rakhna
        $table->time('late_time')->nullable()->default(null)->after('login_end');
    });
}

public function down(): void
{
    Schema::table('attendance_time_windows', function (Blueprint $table) {
        // Rollback ke liye column drop karna
        $table->dropColumn('late_time');
    });
}
};
