<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ENUM me 'paid' aur 'archived' add kar rahe hain (raw query se safely)
        DB::statement("ALTER TABLE salaries MODIFY COLUMN status ENUM('active', 'inactive', 'pending', 'paid', 'archived', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        // Rollback hone par wapas purani state
        DB::statement("ALTER TABLE salaries MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
    }
};