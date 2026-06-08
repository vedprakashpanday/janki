<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_tracking_modules', function (Blueprint $table) {
            $table->id();
            $table->string('task_category_name'); // Jaise: 'Debit Voucher Entry' ya 'Desk Cleaning'
            $table->string('target_table')->nullable(); // Jaise: 'debit_vouchers'
            $table->string('user_id_column')->nullable(); // Jaise: 'approved_by' ya 'assigned_telecaller'
            $table->string('join_column')->nullable(); // Kisse match karna hai, jaise: 'member_id' ya 'id'
            $table->string('date_column')->nullable(); // Jaise: 'created_at' (Aaj ka data filter karne ke liye)
            $table->boolean('is_dynamic')->default(false); // True = Auto track target table se, False = Manual task
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_tracking_modules');
    }
};