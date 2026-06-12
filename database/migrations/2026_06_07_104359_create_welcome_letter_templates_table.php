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
            $table->string('letter_type'); // 'employee', 'member', 'customer', 'other'
            $table->string('entity_type')->nullable(); // 'employee', 'member', 'customer' (Jab specific user ke liye ho)
            $table->string('entity_id')->nullable(); // Specific ID (Jaise 'EMP001', 'MEM005')
            $table->string('title')->nullable();
            $table->longText('content'); // HTML Content (TinyMCE se aayega)
            $table->timestamps();

            // Ek user/type ke liye sirf ek hi record bane isliye composite unique key
            $table->unique(['letter_type', 'entity_type', 'entity_id'], 'template_unique_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('welcome_letter_templates');
    }
};