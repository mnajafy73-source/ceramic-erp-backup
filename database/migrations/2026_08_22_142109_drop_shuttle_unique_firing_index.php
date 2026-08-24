<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // حذف ایندکس با نام دقیق پیدا شده
        DB::statement('DROP INDEX IF EXISTS shuttle_unique_firing');
    }

    public function down(): void
    {
        // بازگرداندن ایندکس (در صورت نیاز)
        Schema::table('shuttle_firings', function (Blueprint $table) {
            $table->unique(['kiln_type', 'year', 'month', 'firing_number'], 'shuttle_unique_firing');
        });
    }
};