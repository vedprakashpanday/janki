<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('reward_days', 5, 2)->default(0)->after('extra_days');
            $table->text('remarks')->nullable()->after('status');
        });
    }
    public function down() {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['reward_days', 'remarks']);
        });
    }
};