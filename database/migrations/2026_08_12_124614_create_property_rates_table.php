<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('property_area_id');
            $table->decimal('rate_amount', 15, 2); // Amount bada ho sakta hai isliye 15,2
            $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('property_area_id')->references('id')->on('property_areas')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_rates');
    }
};