<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف ایندکس‌های قبلی (اگر وجود داشته باشند)
        try {
            DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
        } catch (\Exception $e) {
            // ایندکس وجود ندارد
        }

        try {
            DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_firings_kiln_type_year_month_day_firing_number_unique');
        } catch (\Exception $e) {
            // ایندکس وجود ندارد
        }

        // 2. اضافه کردن فیلد day (اگر وجود نداشته باشد)
        if (!Schema::hasColumn('shuttle_firings', 'day')) {
            Schema::table('shuttle_firings', function (Blueprint $table) {
                $table->integer('day')->nullable()->after('month');
            });
        }

        // 3. پر کردن فیلد day برای رکوردهای موجود
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

        // 5. اصلاح شماره‌های تکراری برای هر گروه (kiln_type, year, month, day)
        // برای هر گروه، شماره‌ها را از ۱ به ترتیب بازنویسی می‌کنیم
        $groups = DB::table('shuttle_firings')
            ->select('kiln_type', 'year', 'month', 'day', DB::raw('MIN(id) as min_id'))
            ->groupBy('kiln_type', 'year', 'month', 'day')
            ->get();

        foreach ($groups as $group) {
            $records = DB::table('shuttle_firings')
                ->where('kiln_type', $group->kiln_type)
                ->where('year', $group->year)
                ->where('month', $group->month)
                ->where('day', $group->day)
                ->orderBy('id')
                ->get();

            $counter = 1;
            foreach ($records as $record) {
                DB::table('shuttle_firings')
                    ->where('id', $record->id)
                    ->update(['firing_number' => $counter]);
                $counter++;
            }
        }

        // 6. ایجاد ایندکس یکتا با (kiln_type, year, month, day, firing_number)
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'day', 'firing_number'], 'shuttle_unique_firing');
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            try {
                DB::statement('ALTER TABLE shuttle_firings DROP INDEX shuttle_unique_firing');
            } catch (\Exception $e) {
                // ایندکس وجود ندارد
            }
            if (Schema::hasColumn('shuttle_firings', 'day')) {
                $table->dropColumn('day');
            }
        });
    }
};