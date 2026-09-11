<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_sales_stat_products', function (Blueprint $table) {
            if (!Schema::hasColumn('user_sales_stat_products', 'manually_added')) {
                $table->boolean('manually_added')->default(true)->after('order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_sales_stat_products', function (Blueprint $table) {
            if (Schema::hasColumn('user_sales_stat_products', 'manually_added')) {
                $table->dropColumn('manually_added');
            }
        });
    }
};