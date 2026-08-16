<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable(); // Null for Head Office
            $table->unsignedBigInteger('phase_id');
            $table->string('type_name');
            $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys (Optional, par data integrity ke liye achha hai)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('phase_id')->references('id')->on('phases')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_types');
    }
};