<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            // Naye columns add kar rahe hain (amount column ke theek baad)
            $table->string('person_name')->nullable()->after('amount');
            $table->string('person_number')->nullable()->after('person_name');
            $table->integer('number_of_persons')->nullable()->default(1)->after('person_number');
        });
    }

    public function down(): void
    {
        Schema::table('travel_allowances', function (Blueprint $table) {
            $table->dropColumn(['person_name', 'person_number', 'number_of_persons']);
        });
    }
};