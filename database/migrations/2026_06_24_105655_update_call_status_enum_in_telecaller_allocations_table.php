<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. SAFETY CLEANUP: Agar table me koi kachra ya invalid status (blank) hai, toh usko pehle 'Pending' kar do taaki Truncate Error na aaye.
        DB::statement("UPDATE telecaller_allocations SET call_status = 'Pending' WHERE call_status NOT IN ('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost')");
        
        DB::statement("UPDATE interested_customers SET status = 'Pending' WHERE status NOT IN ('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost')");

        // 2. APPLY EXACT ENUM: Aapke diye hue 8 purane + 2 naye ('Not Interested', 'Interested')
        DB::statement("ALTER TABLE telecaller_allocations MODIFY COLUMN call_status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested') NOT NULL DEFAULT 'Pending'");
        
        DB::statement("ALTER TABLE interested_customers MODIFY COLUMN status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested') NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback karne par wapas original ENUM
        DB::statement("ALTER TABLE telecaller_allocations MODIFY COLUMN call_status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost') NOT NULL DEFAULT 'Pending'");
        
        DB::statement("ALTER TABLE interested_customers MODIFY COLUMN status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost') NOT NULL DEFAULT 'Pending'");
    }
};