<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_time_windows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // NULL represents Head Office (HO)
            $table->time('login_start');
            $table->time('login_end');
            $table->time('logout_start');
            $table->time('logout_end');
            $table->decimal('min_working_hours', 4, 2)->default(8.25);
            $table->string('status')->default('active'); // active, pending, rejected (For RBAC request/approve flow)
            $table->unsignedBigInteger('action_by')->nullable(); // Admin/Director ID who created/requested this
            $table->text('request_remark')->nullable();
            $table->timestamps();

            // Indexing for faster matrix queries
            $table->index(['company_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_time_windows');
    }
};