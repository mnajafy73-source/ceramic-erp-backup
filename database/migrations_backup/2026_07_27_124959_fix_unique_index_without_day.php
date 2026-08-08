<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // حذف ایندکس قبلی (اگر وجود داشته باشد)
        try {
            DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
        } catch (\Exception $e) {
            // ایندکس وجود ندارد
        }

        // ایجاد ایندکس یکتا روی (kiln_type, year, month, firing_number)
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'firing_number'], 'shuttle_unique_firing');
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            try {
                DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
            } catch (\Exception $e) {}
        });
    }
};