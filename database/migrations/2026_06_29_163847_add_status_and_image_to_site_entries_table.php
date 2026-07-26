<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('site_daily_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('balance_amount');
            $table->string('labour_image')->nullable()->after('status'); // Labour ki image
        });
    }
    public function down() {
        Schema::table('site_daily_entries', function (Blueprint $table) {
            $table->dropColumn(['status', 'labour_image']);
        });
    }
};