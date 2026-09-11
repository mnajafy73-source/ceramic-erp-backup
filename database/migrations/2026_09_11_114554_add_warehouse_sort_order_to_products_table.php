<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'warehouse_sort_order')) {
                $table->integer('warehouse_sort_order')->default(0)->after('hidden_from_warehouse');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'warehouse_sort_order')) {
                $table->dropColumn('warehouse_sort_order');
            }
        });
    }
};