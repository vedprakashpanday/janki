<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // VARCHAR(255) ko TEXT me badal rahe hain taaki limit ka issue na aaye
        DB::statement('ALTER TABLE travel_allowances MODIFY purpose TEXT NULL');
        DB::statement('ALTER TABLE travel_allowances MODIFY destination TEXT NULL');
    }

    public function down(): void
    {
        // Rollback ke waqt wapas VARCHAR(255)
        DB::statement('ALTER TABLE travel_allowances MODIFY purpose VARCHAR(255) NULL');
        DB::statement('ALTER TABLE travel_allowances MODIFY destination VARCHAR(255) NULL');
    }
};