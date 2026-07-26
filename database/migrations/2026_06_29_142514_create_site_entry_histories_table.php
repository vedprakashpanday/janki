<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_entry_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_daily_entry_id');
            $table->unsignedBigInteger('edited_by_id');
            $table->string('action', 50)->default('edited'); // edited, deleted
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();

            $table->foreign('site_daily_entry_id')->references('id')->on('site_daily_entries')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_entry_histories');
    }
};