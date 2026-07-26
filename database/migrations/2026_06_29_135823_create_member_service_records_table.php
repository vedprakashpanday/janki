<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_service_records', function (Blueprint $table) {
            $table->id();
            $table->string('service_code')->unique(); // Code: COMP-M/SVC/Series
            $table->foreignId('member_id_ref')->constrained('members')->onDelete('cascade'); // Member table ka ID
            $table->string('company_code')->nullable();
            $table->string('action_type'); // Promotion, Demotion, Transferred
            $table->date('action_date');
            $table->json('action_details')->nullable(); // Purani aur nayi details rakhne ke liye
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_service_records');
    }
};