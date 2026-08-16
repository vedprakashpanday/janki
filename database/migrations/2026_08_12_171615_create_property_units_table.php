<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('phase_id');
            
            // 🟢 NAYA: Entity Type (Plot hai, Road hai, ya Park?)
            $table->enum('entity_type', ['plot', 'road', 'park', 'temple', 'mosque', 'commercial', 'future_extension', 'other'])->default('plot');
            
            // Ye nullable rahenge kyunki Road/Park ke liye inki zaroorat nahi
            $table->unsignedBigInteger('property_type_id')->nullable();
            $table->unsignedBigInteger('property_category_id')->nullable();
            $table->unsignedBigInteger('property_area_id')->nullable(); 
            
            // Plot No. ya Phir '30Ft Road', 'Central Park' jaise naam
            $table->string('unit_number'); 
            
            $table->json('boundaries')->nullable(); // East, West, North, South
            $table->json('charge_ids')->nullable(); // Additional Facing Charges
            
            // 🟢 NAYA: Map Par Draw Kiye Gaye Coordinates 
            // (e.g., {"type": "polygon", "points": [{"x":10, "y":20}, ...] } )
            $table->json('map_coordinates')->nullable(); 
            
            $table->enum('status', ['active', 'pending', 'inactive'])->default('pending');
            $table->enum('availability_status', ['available', 'booked', 'hold'])->default('available');
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_units');
    }
};