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
        Schema::table('directors', function (Blueprint $table) {
           $table->dropForeign(['company_id']); // Pehle foreign key constraint drop karein
$table->dropColumn('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directors', function (Blueprint $table) {
            //
        });
    }
};
