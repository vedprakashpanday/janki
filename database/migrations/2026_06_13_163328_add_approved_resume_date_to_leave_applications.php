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
           $table->dateTime('approved_resume_datetime')->nullable();
       });
   }

   public function down(): void
   {
       Schema::table('leave_applications', function (Blueprint $table) {
           $table->dropColumn('approved_resume_datetime');
       });
   }
};
