<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('service_id')->nullable()->after('member_id');
            $table->json('transferred_to')->nullable()->after('status');
            
            // mem_status will store employment/associate status
            $table->string('mem_status')->default('On Board')->after('transferred_to');
            
            // Grade column
            $table->string('grade')->nullable()->after('designation_id');
        });
    }

    public function down()
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'transferred_to', 'mem_status', 'grade']);
        });
    }
};