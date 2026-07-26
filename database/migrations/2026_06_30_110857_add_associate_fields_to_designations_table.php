<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('designations', function (Blueprint $table) {
            // Nullable rakha hai taaki non-associate departments mein error na aaye
            $table->string('position')->nullable()->after('status');
            $table->decimal('plot_commission', 10, 2)->nullable()->after('position');
            $table->decimal('construction_commission', 10, 2)->nullable()->after('plot_commission');
        });
    }

    public function down()
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn(['position', 'plot_commission', 'construction_commission']);
        });
    }
};