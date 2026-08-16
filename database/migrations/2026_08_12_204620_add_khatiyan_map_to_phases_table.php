<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('phases', function (Blueprint $table) {
            // Naksha/Base image save karne ke liye
            $table->string('khatiyan_map')->nullable()->after('phase_name');
        });
    }

    public function down()
    {
        Schema::table('phases', function (Blueprint $table) {
            $table->dropColumn('khatiyan_map');
        });
    }
};