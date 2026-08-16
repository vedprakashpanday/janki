<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('letterheads', function (Blueprint $table) {
            // message ya kisi bhi suitable column ke baad add kar lijiye
            $table->enum('status', ['active', 'pending', 'inactive'])->default('active')->after('message');
        });
    }

    public function down()
    {
        Schema::table('letterheads', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};