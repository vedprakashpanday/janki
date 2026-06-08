<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('welcome_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('letter_type')->unique(); // e.g., 'employee', 'member'
            $table->string('title')->nullable();
            $table->longText('content'); // Yahan Admin ka HTML/Text save hoga
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('welcome_letter_templates');
    }
};