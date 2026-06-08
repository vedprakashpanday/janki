<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('employee_logins', function (Blueprint $table) {
        $table->date('s_date_from')->nullable()->after('s_status');
        $table->date('s_date_to')->nullable()->after('s_date_from');
    });
}

public function down()
{
    Schema::table('employee_logins', function (Blueprint $table) {
        $table->dropColumn(['s_date_from', 's_date_to']);
    });
}
};
