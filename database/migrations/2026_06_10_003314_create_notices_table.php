<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Notice ka subject/title
            $table->longText('content'); // TinyMCE ka HTML content
            $table->date('notice_date'); 
            $table->string('target_audience'); // 'employee', 'member', 'customer', 'specific'
            $table->string('entity_type')->nullable(); // Agar specific hai to: 'employee', 'member', 'customer'
            $table->string('entity_id')->nullable(); // Specific ID jaise EMP001
            $table->boolean('requires_reply')->default(0); // 1 = Reply allowed, 0 = No reply
            $table->unsignedBigInteger('company_id')->nullable(); // Kis company ke admin ne bheja
            $table->string('created_by')->nullable(); // Jisne create kiya uski ID
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notices');
    }
};