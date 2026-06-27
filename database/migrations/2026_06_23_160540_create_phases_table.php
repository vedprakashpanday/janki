<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('phase_name');
            $table->string('phase_location');
            $table->text('phase_details');
            $table->string('phase_image')->nullable();
            $table->string('phase_google_map_url')->nullable(); // Optional field
            $table->unsignedBigInteger('created_by')->nullable(); // Tracking ke liye ki kisne banaya
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('phases');
    }
};