<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // ENUM modify karne ke liye Raw SQL sabse safe approach hai
        DB::statement("ALTER TABLE debit_vouchers MODIFY COLUMN status ENUM('active', 'inactive', 'pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        // Rollback hone par purani state me le jane ke liye
        DB::statement("ALTER TABLE debit_vouchers MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
    }
};