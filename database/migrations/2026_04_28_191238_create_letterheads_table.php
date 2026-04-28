<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('letterheads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->string('ref_no')->unique();
            $table->year('ref_year');
            $table->date('letter_date');
            $table->string('emp_code')->nullable();
            $table->string('subject')->nullable();
            $table->string('paid_to')->nullable();
            $table->text('paid_to_address')->nullable();
            $table->longText('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letterheads');
    }
};