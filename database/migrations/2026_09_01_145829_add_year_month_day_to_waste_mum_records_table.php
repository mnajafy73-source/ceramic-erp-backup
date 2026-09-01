<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            // اگر ستون‌ها وجود ندارند، اضافه کن
            if (!Schema::hasColumn('waste_mum_records', 'year')) {
                $table->integer('year')->nullable();
            }
            if (!Schema::hasColumn('waste_mum_records', 'month')) {
                $table->integer('month')->nullable();
            }
            if (!Schema::hasColumn('waste_mum_records', 'day')) {
                $table->integer('day')->nullable();
            }
        });

        // همچنین برای جدول shoulder_records هم اگر نیاز است (احتمالاً وجود دارد)
        Schema::table('shoulder_records', function (Blueprint $table) {
            if (!Schema::hasColumn('shoulder_records', 'year')) {
                $table->integer('year')->nullable();
            }
            if (!Schema::hasColumn('shoulder_records', 'month')) {
                $table->integer('month')->nullable();
            }
            if (!Schema::hasColumn('shoulder_records', 'day')) {
                $table->integer('day')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day']);
        });

        Schema::table('shoulder_records', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day']);
        });
    }
};