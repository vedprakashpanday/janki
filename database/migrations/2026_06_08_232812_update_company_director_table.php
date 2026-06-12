<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_director', function (Blueprint $table) {
            // director_id ko nullable banayein taaki CEO aane par ye blank reh sake
            $table->unsignedBigInteger('director_id')->nullable()->change();
            // Naya ceo_id column add karein
            $table->unsignedBigInteger('ceo_id')->nullable()->after('director_id');
        });
    }

    public function down()
    {
        Schema::table('company_director', function (Blueprint $table) {
            $table->dropColumn('ceo_id');
            $table->unsignedBigInteger('director_id')->nullable(false)->change();
        });
    }
};