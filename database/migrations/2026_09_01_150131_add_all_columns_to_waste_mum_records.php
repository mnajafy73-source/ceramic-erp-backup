<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            // اضافه کردن همه‌ی ستون‌های موردنیاز
            if (!Schema::hasColumn('waste_mum_records', 'year')) {
                $table->integer('year')->nullable();
            }
            if (!Schema::hasColumn('waste_mum_records', 'month')) {
                $table->integer('month')->nullable();
            }
            if (!Schema::hasColumn('waste_mum_records', 'day')) {
                $table->integer('day')->nullable();
            }
            if (!Schema::hasColumn('waste_mum_records', 'product_name')) {
                $table->string('product_name');
            }
            if (!Schema::hasColumn('waste_mum_records', 'amount')) {
                $table->integer('amount')->default(0);
            }
        });

        // برای اطمینان، جدول shoulder_records رو هم چک می‌کنیم
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
            if (!Schema::hasColumn('shoulder_records', 'product_name')) {
                $table->string('product_name');
            }
            if (!Schema::hasColumn('shoulder_records', 'total')) {
                $table->integer('total')->default(0);
            }
            if (!Schema::hasColumn('shoulder_records', 'carton_count')) {
                $table->integer('carton_count')->default(0);
            }
            if (!Schema::hasColumn('shoulder_records', 'per_carton')) {
                $table->integer('per_carton')->default(0);
            }
            if (!Schema::hasColumn('shoulder_records', 'shoulder')) {
                $table->integer('shoulder')->default(0);
            }
            if (!Schema::hasColumn('shoulder_records', 'name')) {
                $table->string('name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day', 'product_name', 'amount']);
        });

        Schema::table('shoulder_records', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day', 'product_name', 'total', 'carton_count', 'per_carton', 'shoulder', 'name']);
        });
    }
};