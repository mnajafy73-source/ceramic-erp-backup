<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // بررسی وجود ایندکس تکراری و حذف آن
        try {
            DB::statement('ALTER TABLE sales DROP INDEX sales_invoice_number_unique');
        } catch (\Exception $e) {}

        Schema::table('sales', function (Blueprint $table) {
            $table->unique('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_invoice_number_unique');
        });
    }
};