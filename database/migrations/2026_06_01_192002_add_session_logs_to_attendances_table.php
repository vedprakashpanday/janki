<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // JSON column to store multiple login/logout records
            $table->json('session_logs')->nullable()->after('logout_time'); 
        });
    }

    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('session_logs');
        });
    }
};
