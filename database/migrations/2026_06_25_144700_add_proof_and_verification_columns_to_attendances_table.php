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
        Schema::table('attendances', function (Blueprint $table) {
            $table->json('punch_proof_images')->nullable()->after('session_logs'); // To store multiple image paths
            $table->text('punch_reason')->nullable()->after('punch_proof_images'); // Description from employee
            $table->enum('hr_verification_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('punch_reason'); 
            $table->text('hr_remark')->nullable()->after('hr_verification_status'); // Mandatory reason when HR changes status
            $table->boolean('is_late_punch')->default(false)->after('hr_remark'); // Flagged if punch-in > login_start + 30 mins
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'punch_proof_images',
                'punch_reason',
                'hr_verification_status',
                'hr_remark',
                'is_late_punch'
            ]);
        });
    }
};