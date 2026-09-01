<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // جدول waste_mum_records
        Schema::table('waste_mum_records', function (Blueprint $table) {
            if (!Schema::hasColumn('waste_mum_records', 'product_name')) {
                $table->string('product_name')->nullable();
            }
            // اگر ستون‌های دیگر هم کم بود، اضافه کنید
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

        // همچنین جدول shoulder_records را چک کنید (احتمالاً نیاز دارد)
        Schema::table('shoulder_records', function (Blueprint $table) {
            if (!Schema::hasColumn('shoulder_records', 'product_name')) {
                $table->string('product_name')->nullable();
            }
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
            $table->dropColumn(['product_name', 'year', 'month', 'day']);
        });

        Schema::table('shoulder_records', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'year', 'month', 'day']);
        });
    }
};