<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('visitors', function (Blueprint $table) {
        // Adding the new date column
        $table->date('visiting_date')->nullable()->after('whom_to_meet');
    });

    // Existing data ka visiting_date set kar dete hain taaki purani reports kharab na hon
    \Illuminate\Support\Facades\DB::statement('UPDATE visitors SET visiting_date = DATE(time_in) WHERE visiting_date IS NULL');
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            //
        });
    }
};
