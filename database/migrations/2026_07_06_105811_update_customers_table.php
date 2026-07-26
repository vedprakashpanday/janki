<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // पुरानी फील्ड्स हटाना
            $table->dropColumn(['booking_date', 'so_do_wo']);

            // नई फील्ड्स जोड़ना (सही जगह पर)
            $table->date('joining_date')->nullable()->after('password');
            $table->string('customer_code')->after('customer_id')->index();
            $table->string('father_name')->nullable()->after('customer_name');
            $table->string('spouse_name')->nullable()->after('father_name');

            // Soft Deletes (deleted_at कॉलम)
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // रोलबैक के समय पुरानी फील्ड्स वापस लाना
            $table->date('booking_date')->nullable();
            $table->string('so_do_wo')->nullable();

            // नई फील्ड्स और सॉफ्ट डिलीट हटाना
            $table->dropColumn([
                'joining_date', 
                'customer_code', 
                'father_name', 
                'spouse_name'
            ]);
            $table->dropSoftDeletes();
        });
    }
};