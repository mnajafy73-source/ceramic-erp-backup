<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف ایندکس قبلی (اگر وجود داشته باشد)
        try {
            DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
        } catch (\Exception $e) {
            // ایندکس وجود ندارد، خطا نادیده گرفته می‌شود
        }

        // 2. ایجاد ایندکس جدید روی (kiln_type, year, month, day)
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'day'], 'shuttle_unique_day');
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            try {
                DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_day');
            } catch (\Exception $e) {
                // ایندکس وجود ندارد
            }
            // بازگرداندن ایندکس قبلی (اختیاری)
            $table->unique(['kiln_type', 'year', 'month', 'firing_number'], 'shuttle_unique_firing');
        });
    }
};