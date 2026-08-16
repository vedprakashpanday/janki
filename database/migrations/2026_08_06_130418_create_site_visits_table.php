<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_visit_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_visit_id');
            $table->string('media_path'); // Service se compressed file ka path
            $table->timestamps();

            $table->foreign('site_visit_id')->references('id')->on('site_visits')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_visit_images');
    }
};