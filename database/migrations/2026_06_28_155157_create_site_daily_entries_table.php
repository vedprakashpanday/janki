<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_allocation_id');
            $table->unsignedBigInteger('employee_id');
            $table->date('entry_date');
            
            $table->string('category', 100)->comment('Labour, Goods Carrier, Material, etc.');
            
            // Financial fields
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('balance_amount', 15, 2)->default(0.00);
            
            // THE MASTER JSON COLUMN 
            $table->json('entry_details')->comment('Dynamic fields based on category');
            
            $table->timestamps();

            $table->foreign('site_allocation_id')->references('id')->on('site_incharge_allocations')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_daily_entries');
    }
};