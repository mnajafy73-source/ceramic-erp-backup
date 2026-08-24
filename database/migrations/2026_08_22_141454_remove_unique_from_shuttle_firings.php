<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            // حذف UNIQUE constraint روی ترکیب (kiln_type, year, month, firing_number)
            $table->dropUnique(['kiln_type', 'year', 'month', 'firing_number']);
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'firing_number']);
        });
    }
};