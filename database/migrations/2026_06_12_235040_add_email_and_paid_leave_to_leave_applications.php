<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
   {
       Schema::table('leave_applications', function (Blueprint $table) {
           $table->string('emergency_email')->nullable();
           $table->boolean('is_paid_leave')->default(0);
       });
   }

   public function down(): void
   {
       Schema::table('leave_applications', function (Blueprint $table) {
           $table->dropColumn(['emergency_email', 'is_paid_leave']);
       });
   }
};
