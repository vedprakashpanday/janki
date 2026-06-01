<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            // Marital status ke theek baad anniversary_date column add kar rahe hain
            $table->date('anniversary_date')->nullable()->after('marital_status');
        });
    }

    public function down(): void
    {
        Schema::table('super_admins', function (Blueprint $table) {
            $table->dropColumn('anniversary_date');
        });
    }
};