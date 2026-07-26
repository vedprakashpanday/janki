<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        // Pehle ensure karo ki existing data purely numeric hai,
        // warna CAST/MODIFY fail ho sakta hai ya data corrupt ho sakta hai.
        // (Agar koi non-numeric ya blank-string value hui to use NULL kar denge.)
        DB::table('designations')
            ->where(function ($q) {
                $q->where('position', '')
                  ->orWhereRaw("position REGEXP '[^0-9]'");
            })
            ->update(['position' => null]);

        // Ab column type ko integer mein convert karo
        DB::statement('ALTER TABLE designations MODIFY position INT UNSIGNED NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE designations MODIFY position VARCHAR(255) NULL DEFAULT NULL');
    }
};
