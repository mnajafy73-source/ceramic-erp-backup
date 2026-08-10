<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            // ابتدا ایندکس را حذف کن (اگر وجود دارد)
            $table->dropUnique(['kiln_type', 'year', 'month', 'day', 'firing_number']);
        });

        Schema::table('shuttle_firings', function (Blueprint $table) {
            // سپس دوباره ایجاد کن
            $table->unique(['kiln_type', 'year', 'month', 'day', 'firing_number'], 'shuttle_unique_firing');
        });
    }

    public function down()
    {
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropUnique('shuttle_unique_firing');
        });
    }
};