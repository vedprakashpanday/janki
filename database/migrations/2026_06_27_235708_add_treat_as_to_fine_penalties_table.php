<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('fine_penalties', function (Blueprint $table) {
        // 'treat_as' column add kar rahe hain (warning, final, apply)
        $table->enum('treat_as', ['warning', 'final', 'apply'])->default('apply')->after('description');
    });
}

public function down()
{
    Schema::table('fine_penalties', function (Blueprint $table) {
        $table->dropColumn('treat_as');
    });
}
};
