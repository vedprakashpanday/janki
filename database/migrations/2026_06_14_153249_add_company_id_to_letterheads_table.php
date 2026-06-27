<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('letterheads', function (Blueprint $table) {
            // company_id ko nullable banaya hai taaki 'Global' (Master Company) ke case me null save ho sake
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            
            // Agar branch_id pehle se nullable nahi hai, toh isko bhi nullable karna zaroori hai (Head Office ke liye)
            // Agar pehle se hai, toh is line ko comment kar dijiye
            $table->unsignedBigInteger('branch_id')->nullable()->change();

            // Optional: Foreign key constraint (Agar aap delete cascade chahte hain)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('letterheads', function (Blueprint $table) {
            // $table->dropForeign(['company_id']); // Agar foreign key use kiya hai toh ise uncomment karein
            $table->dropColumn('company_id');
        });
    }
};