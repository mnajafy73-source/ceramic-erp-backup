<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. حذف ستون kiln_type از products (اگر وجود داشته باشد)
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'kiln_type')) {
                $table->dropColumn('kiln_type');
            }
        });

        // 2. ساخت جدول tonneli_firing_items (محصولات پخت تونلی)
        Schema::create('tonneli_firing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tonneli_firing_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('input_quantity', 15, 2)->default(0);
            $table->decimal('output_quantity', 15, 2)->default(0);
            $table->boolean('is_packaged')->default(false);
            $table->timestamps();
        });

        // 3. انتقال داده‌های موجود از tonneli_firings به جدول جدید (اگر داده وجود دارد)
        $oldFirings = DB::table('tonneli_firings')->get();
        foreach ($oldFirings as $old) {
            DB::table('tonneli_firing_items')->insert([
                'tonneli_firing_id' => $old->id,
                'product_id' => $old->product_id,
                'input_quantity' => $old->input_quantity,
                'output_quantity' => $old->output_quantity,
                'is_packaged' => $old->is_packaged,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ]);
        }

        // 4. حذف ستون‌های قدیمی از tonneli_firings
        Schema::table('tonneli_firings', function (Blueprint $table) {
            if (Schema::hasColumn('tonneli_firings', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('tonneli_firings', 'input_quantity')) {
                $table->dropColumn('input_quantity');
            }
            if (Schema::hasColumn('tonneli_firings', 'output_quantity')) {
                $table->dropColumn('output_quantity');
            }
            if (Schema::hasColumn('tonneli_firings', 'is_packaged')) {
                $table->dropColumn('is_packaged');
            }
        });
    }

    public function down(): void
    {
        // بازگرداندن ستون‌ها به tonneli_firings
        Schema::table('tonneli_firings', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('input_quantity', 15, 2)->default(0);
            $table->decimal('output_quantity', 15, 2)->default(0);
            $table->boolean('is_packaged')->default(false);
        });

        // بازگرداندن داده‌ها
        $items = DB::table('tonneli_firing_items')->get();
        foreach ($items as $item) {
            DB::table('tonneli_firings')
                ->where('id', $item->tonneli_firing_id)
                ->update([
                    'product_id' => $item->product_id,
                    'input_quantity' => $item->input_quantity,
                    'output_quantity' => $item->output_quantity,
                    'is_packaged' => $item->is_packaged,
                ]);
        }

        Schema::dropIfExists('tonneli_firing_items');

        Schema::table('products', function (Blueprint $table) {
            $table->enum('kiln_type', ['tonneli', 'shuttle', 'both'])->nullable()->after('unit_id');
        });
    }
};