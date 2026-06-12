<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('holidays', function (Blueprint $table) {
        $table->id();
        $table->foreignId('notice_id')->constrained('notices')->onDelete('cascade');
        $table->integer('total_days')->default(1);
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->string('status')->default('active'); // Isko baad me cron se 'inactive' karenge jab date nikal jayegi
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
