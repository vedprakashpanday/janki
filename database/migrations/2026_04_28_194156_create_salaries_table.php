<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('basic_pay', 10, 2)->default(0.00);
            $table->decimal('hra', 10, 2)->default(0.00);
            $table->decimal('da', 10, 2)->default(0.00);
            $table->decimal('medical_allowance', 10, 2)->default(0.00);
            $table->decimal('travel_allowance', 10, 2)->default(0.00);
            $table->decimal('other_allowance', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};