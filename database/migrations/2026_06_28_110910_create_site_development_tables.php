<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Projects Table
        Schema::create('site_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('project_name');
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'completed', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Master Table: Labours
        Schema::create('site_labours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_no')->nullable();
            $table->decimal('default_rate', 10, 2)->default(0); 
            $table->timestamps();
        });

        // 3. Master Table: Vehicles
        Schema::create('site_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_no')->unique();
            $table->string('vehicle_type')->nullable(); 
            $table->decimal('default_rate', 10, 2)->default(0); 
            $table->timestamps();
        });

        // 4. Master Table: Materials (NAYA JODA GAYA HAI)
        Schema::create('site_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material_name'); // e.g., Cement, Chhad/Steel, Sand
            $table->string('unit'); // e.g., Bags, Kg, Ton, Tractor
            $table->decimal('default_rate', 10, 2)->default(0); 
            $table->timestamps();
        });

        // 5. Main Site Reports Table
        Schema::create('site_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('supervisor_id'); 
            $table->date('report_date');
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('site_projects')->onDelete('cascade');
        });

        // 6. Pivot Table: Labours
        Schema::create('site_report_labours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('labour_id');
            $table->decimal('hours_worked', 5, 2)->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0); 
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('site_reports')->onDelete('cascade');
            $table->foreign('labour_id')->references('id')->on('site_labours')->onDelete('cascade');
        });

        // 7. Pivot Table: Vehicles
        Schema::create('site_report_vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->integer('trips')->default(0);
            $table->decimal('rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0); 
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('site_reports')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('site_vehicles')->onDelete('cascade');
        });

        // 8. Pivot Table: Materials (NAYA JODA GAYA HAI)
        Schema::create('site_report_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('quantity', 10, 2)->default(0); // Kitna aaya (e.g., 50 Bags)
            $table->decimal('rate', 10, 2)->default(0); // Per unit rate
            $table->decimal('total_amount', 12, 2)->default(0); // quantity * rate
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('site_reports')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('site_materials')->onDelete('cascade');
        });

        // 9. Documents/Bills Table
        Schema::create('site_report_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('file_path');
            $table->string('file_type')->nullable(); 
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('site_reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_report_documents');
        Schema::dropIfExists('site_report_materials');
        Schema::dropIfExists('site_report_vehicles');
        Schema::dropIfExists('site_report_labours');
        Schema::dropIfExists('site_reports');
        Schema::dropIfExists('site_materials');
        Schema::dropIfExists('site_vehicles');
        Schema::dropIfExists('site_labours');
        Schema::dropIfExists('site_projects');
    }
};