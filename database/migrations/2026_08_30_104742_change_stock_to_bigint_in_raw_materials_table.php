<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            // تغییر نوع ستون stock به bigInteger با مقدار پیش‌فرض 0
            $table->bigInteger('stock')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            // برگرداندن به حالت قبلی (integer)
            $table->integer('stock')->default(0)->change();
        });
    }
};