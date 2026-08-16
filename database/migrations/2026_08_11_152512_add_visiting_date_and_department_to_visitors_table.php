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
    Schema::table('visitors', function (Blueprint $table) {
       
        $table->string('person_department')->nullable()->after('purpose');
    });

    // Existing data ki visiting_date set kar dete hain
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
