<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'unpackaged_manual_stock')) {
                // nullable چون اگه null باشه، از فرمول محاسبه می‌شه
                $table->decimal('unpackaged_manual_stock', 15, 2)->nullable()->after('all_stocks_sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'unpackaged_manual_stock')) {
                $table->dropColumn('unpackaged_manual_stock');
            }
        });
    }
};