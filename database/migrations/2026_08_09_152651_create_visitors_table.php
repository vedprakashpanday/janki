<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            
            // Core Visitor Details
            $table->string('visitor_name');
            $table->text('visitor_address')->nullable();
            $table->string('visitor_mobile', 20);
            $table->string('purpose')->nullable();
            $table->string('whom_to_meet')->nullable();
            
            // Timestamps for entry and exit
            $table->timestamp('time_in')->useCurrent(); // By default jab entry banegi tab ka time
            $table->timestamp('time_out')->nullable(); // Ye baad me update hoga jab wo jayega
            
            // Photo Path
            $table->string('photo')->nullable(); // Sirf optimized webp path save hoga
            
            // Tracking
            $table->unsignedBigInteger('created_by')->nullable(); // Kisne entry ki (Employee/Admin id)
            $table->timestamps();

            // 🔥 Performance Indexes (Reports aur Directory jaldi load karne ke liye)
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('created_at'); // Date wise filter fast karne ke liye
        });
    }

    public function down()
    {
        Schema::dropIfExists('visitors');
    }
};