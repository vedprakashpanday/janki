<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Laravel automatically index ka naam 'table_column_unique' dhoondh lega aur hata dega
            $table->dropUnique(['designation_code']);
            $table->dropUnique(['designation_name']);
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Agar kabhi rollback karna pada toh wapas unique lag jayega
            $table->unique('designation_code');
            $table->unique('designation_name');
        });
    }
};