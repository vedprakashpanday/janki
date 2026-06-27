<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Safety Cleanup: Agar koi kachra ho toh pehle clean karo
        DB::statement("UPDATE telecaller_allocations SET call_status = 'Pending' WHERE call_status NOT IN ('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested')");
        DB::statement("UPDATE interested_customers SET status = 'Pending' WHERE status NOT IN ('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested','General')");

        // Naye 3 options add karna: 'Switch Off', 'Not Answering', 'Busy'
        DB::statement("ALTER TABLE telecaller_allocations MODIFY COLUMN call_status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested','Switch Off','Not Answering','Busy') NOT NULL DEFAULT 'Pending'");
        
        DB::statement("ALTER TABLE interested_customers MODIFY COLUMN status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested','General','Switch Off','Not Answering','Busy') NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        // Rollback hone par wapas purana state
        DB::statement("ALTER TABLE telecaller_allocations MODIFY COLUMN call_status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested') NOT NULL DEFAULT 'Pending'");
        DB::statement("ALTER TABLE interested_customers MODIFY COLUMN status ENUM('Pending','Connected','Not Reachable','Follow Up','Site Visit Scheduled','Site Visit Done','Booking Done','Lost','Not Interested','Interested','General') NOT NULL DEFAULT 'Pending'");
    }
};