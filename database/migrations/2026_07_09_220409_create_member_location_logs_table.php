<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_location_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->date('log_date');
            $table->decimal('latitude', 10, 8); // Google maps standard precision
            $table->decimal('longitude', 11, 8);
            $table->timestamp('tracked_at'); // Jis exact time par location aayi
            $table->timestamps();

            // Indexing for faster queries on admin dashboard
            $table->index(['member_id', 'log_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_location_logs');
    }
};