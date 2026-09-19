<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ۱. ساخت واحد «عدد» اگه نبود
        if (!DB::table('units')->where('name', 'عدد')->exists()) {
            DB::table('units')->insert([
                'name' => 'عدد',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ۲. ست کردن firing_process = 'both' برای همه کالاها
        if (Schema::hasColumn('products', 'firing_process')) {
            DB::table('products')->update(['firing_process' => 'both']);
        }

        // ۳. حذف ستون cavities
        if (Schema::hasColumn('products', 'cavities')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('cavities');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('cavities')->default(1);
        });
    }
};