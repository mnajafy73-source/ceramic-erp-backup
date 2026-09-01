<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            // بررسی می‌کنیم که ستون‌ها وجود نداشته باشند تا خطا ندهد
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
    }

    public function down(): void
    {
        Schema::table('waste_mum_records', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day']);
        });
    }
};