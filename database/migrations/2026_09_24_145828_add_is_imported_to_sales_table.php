<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'is_imported')) {
            Schema::table('sales', function (Blueprint $table) {
                // ✅ پیش‌فرض true — چون گفتی همه فروش‌ها از اکسل اومدن
                $table->boolean('is_imported')->default(true)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'is_imported')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('is_imported');
            });
        }
    }
};