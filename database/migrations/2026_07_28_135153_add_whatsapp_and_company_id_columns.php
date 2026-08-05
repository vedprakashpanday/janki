<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add whatsapp_no to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->string('whatsapp_no', 20)->nullable()->after('phone');
        });

        // Add company_id to directors table
        // Schema::table('directors', function (Blueprint $table) {
        //     $table->unsignedBigInteger('company_id')->nullable()->after('id');
        //     // Un-comment the line below if you want to enforce strict foreign key constraints
        //     // $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        // });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('whatsapp_no');
        });

        // Schema::table('directors', function (Blueprint $table) {
        //     // $table->dropForeign(['company_id']); // Un-comment if you added the foreign key above
        //     $table->dropColumn('company_id');
        // });
    }
};