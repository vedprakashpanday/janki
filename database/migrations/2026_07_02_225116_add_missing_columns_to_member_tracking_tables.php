<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. member_devices table mein device_code add karna
        Schema::table('member_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('member_devices', 'device_code')) {
                $table->string('device_code')->after('device_token')->nullable();
            }
        });

        // 2. member_login_sessions table mein device_code aur status add karna
        Schema::table('member_login_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('member_login_sessions', 'device_code')) {
                $table->string('device_code')->after('member_device_id')->nullable();
            }
            if (!Schema::hasColumn('member_login_sessions', 'status')) {
                $table->string('status')->after('logout_lng')->default('Success'); // Success ya Blocked
            }
        });
    }

    public function down()
    {
        Schema::table('member_devices', function (Blueprint $table) {
            $table->dropColumn(['device_code']);
        });

        Schema::table('member_login_sessions', function (Blueprint $table) {
            $table->dropColumn(['device_code', 'status']);
        });
    }
};