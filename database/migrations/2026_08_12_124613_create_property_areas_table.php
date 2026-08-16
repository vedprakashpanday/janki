<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('property_category_id');
            $table->string('area_name');
            $table->string('measurement_unit')->nullable()->default('Sq Ft'); 
            $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('property_category_id')->references('id')->on('property_categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_areas');
    }
};