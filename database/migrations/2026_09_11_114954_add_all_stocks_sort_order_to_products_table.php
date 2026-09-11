<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'all_stocks_sort_order')) {
                $table->integer('all_stocks_sort_order')->default(0)->after('warehouse_sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'all_stocks_sort_order')) {
                $table->dropColumn('all_stocks_sort_order');
            }
        });
    }
};