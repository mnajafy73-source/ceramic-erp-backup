<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. ستون موقت برای نگهداری داده‌ها
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->string('kiln_type_temp')->nullable();
        });

        DB::statement('UPDATE shuttle_firings SET kiln_type_temp = kiln_type');

        // ۲. حذف ستون قدیمی
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn('kiln_type');
        });

        // ۳. ایجاد ستون جدید با enum توسعه‌یافته
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->enum('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4', 'packaging'])->nullable();
        });

        // ۴. بازگرداندن داده‌ها
        DB::statement('UPDATE shuttle_firings SET kiln_type = kiln_type_temp');

        // ۵. حذف ستون موقت
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn('kiln_type_temp');
        });
    }

    public function down(): void
    {
        // برگشت به حالت قبل
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->string('kiln_type_temp')->nullable();
        });

        DB::statement('UPDATE shuttle_firings SET kiln_type_temp = kiln_type');

        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn('kiln_type');
        });

        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->enum('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'packaging'])->nullable();
        });

        DB::statement("UPDATE shuttle_firings SET kiln_type = kiln_type_temp WHERE kiln_type_temp IN ('kiln_1', 'kiln_2', 'kiln_3', 'packaging')");

        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->dropColumn('kiln_type_temp');
        });
    }
};