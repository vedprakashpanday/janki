<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable(); // Plots, Villa, Plots & Villa, General
            $table->text('question'); // User ka sawal ya button ka text
            $table->text('answer')->nullable(); // Admin ka raw jawab ya Gemini ka Pro jawab
            $table->text('keywords')->nullable(); // Search karne ke liye keywords
            $table->boolean('is_pro_reply')->default(0); // 0 = Raw, 1 = Gemini optimized
            $table->enum('status', ['active', 'unanswered'])->default('active'); // Naye sawalo ko unanswered me daalenge
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('faqs');
    }
};