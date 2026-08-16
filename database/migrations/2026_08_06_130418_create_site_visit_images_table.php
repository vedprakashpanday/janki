<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_visit_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('min_visits')->default(0);
            $table->integer('max_visits')->nullable(); // Null means unlimited above min
            $table->decimal('amount', 10, 2)->default(0.00); // Payout amount
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_visit_settings');
    }
};