<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->string('company_name');
    $table->string('company_code', 10)->unique();
    $table->unsignedBigInteger('parent_id')->nullable(); 
    
    // Naye Columns
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('state')->nullable();
    $table->string('district')->nullable();
    $table->text('address')->nullable();
    $table->string('gst_no')->nullable();
    
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
    $table->foreign('parent_id')->references('id')->on('companies')->onDelete('cascade');
});
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};