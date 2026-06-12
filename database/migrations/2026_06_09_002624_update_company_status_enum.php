<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        // Raw DB statement for ENUM update
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'active'");
    }

    public function down() {
        DB::statement("ALTER TABLE companies MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    }
};