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
       Schema::table('leave_applications', function (Blueprint $table) {
           $table->dateTime('approved_start_datetime')->nullable();
           $table->dateTime('approved_end_datetime')->nullable();
       });
   }

   public function down(): void
   {
       Schema::table('leave_applications', function (Blueprint $table) {
           $table->dropColumn(['approved_start_datetime', 'approved_end_datetime']);
       });
   }
};
