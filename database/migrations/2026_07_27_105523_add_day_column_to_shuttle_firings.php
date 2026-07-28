<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف ایندکس قبلی (اگر وجود داشته باشد) با DB::statement
        try {
            DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
        } catch (\Exception $e) {
            // اگر ایندکس وجود نداشت، کاری نمی‌کنیم
        }

        // 2. فیلد day را اضافه می‌کنیم (اگر وجود نداشته باشد)
        if (!Schema::hasColumn('shuttle_firings', 'day')) {
            Schema::table('shuttle_firings', function (Blueprint $table) {
                $table->integer('day')->nullable()->after('month');
            });
        }

        // 3. فیلد day را برای رکوردهای موجود پر می‌کنیم
        $records = DB::table('shuttle_firings')->get();
        foreach ($records as $record) {
            $day = date('d', strtotime($record->date));
            DB::table('shuttle_firings')
                ->where('id', $record->id)
                ->update(['day' => $day]);
        }

        // 4. فیلد day را NOT NULL می‌کنیم
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->integer('day')->nullable(false)->change();
        });

        // 5. ایندکس یکتا با اضافه کردن day (اگر وجود نداشته باشد)
        try {
            Schema::table('shuttle_firings', function (Blueprint $table) {
                $table->unique(['kiln_type', 'year', 'month', 'day', 'firing_number'], 'shuttle_unique_firing');
            });
        } catch (\Exception $e) {
            // اگر ایندکس قبلاً وجود داشت، خطا نده
        }
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            try {
                DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
            } catch (\Exception $e) {
                // ایندکس وجود ندارد
            }
            $table->dropColumn('day');
        });
    }
};