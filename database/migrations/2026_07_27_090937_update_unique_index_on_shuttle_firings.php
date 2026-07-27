<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ابتدا ایندکس قبلی را حذف می‌کنیم (اگر وجود داشته باشد)
        Schema::table('shuttle_firings', function (Blueprint $table) {
            if (Schema::hasTable('shuttle_firings')) {
                $table->dropUnique('shuttle_unique_firing');
            }
        });

        // ایندکس جدید با اضافه کردن product_id
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'firing_number', 'product_id'], 'shuttle_unique_firing');
        });
    }

    public function down(): void
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropUnique('shuttle_unique_firing');
            // در صورت بازگشت، ایندکس قبلی را برمی‌گردانیم (بدون product_id)
            $table->unique(['kiln_type', 'year', 'month', 'firing_number'], 'shuttle_unique_firing');
        });
    }
};