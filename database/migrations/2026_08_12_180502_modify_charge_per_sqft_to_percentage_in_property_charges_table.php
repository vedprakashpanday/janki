<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('property_charges', function (Blueprint $table) {
            // Replaces charge_per_sqft with charge_percentage
            if (Schema::hasColumn('property_charges', 'charge_per_sqft')) {
                $table->renameColumn('charge_per_sqft', 'charge_percentage');
            } else {
                $table->decimal('charge_percentage', 5, 2)->default(0)->after('charge_name');
            }
        });
    }

    public function down()
    {
        Schema::table('property_charges', function (Blueprint $table) {
            if (Schema::hasColumn('property_charges', 'charge_percentage')) {
                $table->renameColumn('charge_percentage', 'charge_per_sqft');
            }
        });
    }
};