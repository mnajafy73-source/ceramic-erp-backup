<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tonneli_firings', 'is_imported')) {
            Schema::table('tonneli_firings', function (Blueprint $table) {
                // ✅ پیش‌فرض true برای رکوردهای فعلی (چون همه از اکسل اومدن)
                $table->boolean('is_imported')->default(true)->after('date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tonneli_firings', 'is_imported')) {
            Schema::table('tonneli_firings', function (Blueprint $table) {
                $table->dropColumn('is_imported');
            });
        }
    }
};