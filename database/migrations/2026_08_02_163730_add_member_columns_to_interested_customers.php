<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->boolean('is_member')->default(0)->after('status');
            $table->string('member_id')->nullable()->after('is_member');
        });
    }

    public function down(): void
    {
        Schema::table('interested_customers', function (Blueprint $table) {
            $table->dropColumn(['is_member', 'member_id']);
        });
    }
};