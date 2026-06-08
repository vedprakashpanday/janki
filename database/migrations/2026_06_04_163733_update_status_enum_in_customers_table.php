<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Enum column update karne ke liye DB statement sabse safe hota hai
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'active'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
    }
};