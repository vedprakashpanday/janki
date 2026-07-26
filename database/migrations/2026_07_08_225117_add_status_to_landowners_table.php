<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landowners', function (Blueprint $table) {
            // Default active set kiya ja raha hai
            $table->string('status')->default('active')->after('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('landowners', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};