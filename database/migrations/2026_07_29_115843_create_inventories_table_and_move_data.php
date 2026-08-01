<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ساخت جدول inventories
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->integer('box')->default(0);
            $table->integer('pallet')->default(0);
            $table->integer('layer')->default(0);
            $table->timestamps();
        });

        // 2. انتقال داده‌های موجودی از products.initial_stock به inventories
        $products = DB::table('products')->where('initial_stock', '>', 0)->get();
        foreach ($products as $product) {
            // محاسبه کارتن، پالت و لایه (با فرض اینکه فیلدهای per_box, per_pallet, layers_per_box وجود دارند)
            $perBox = $product->per_box ?? 0;
            $perPallet = $product->per_pallet ?? 0;
            $layersPerBox = $product->layers_per_box ?? 0;

            $box = 0;
            $pallet = 0;
            $layer = 0;

            if ($perBox > 0) {
                $box = intval($product->initial_stock / $perBox);
            }
            if ($perPallet > 0) {
                $pallet = intval($product->initial_stock / $perPallet);
            }
            if ($layersPerBox > 0 && $perBox > 0) {
                $perLayer = $perBox * $layersPerBox;
                $layer = intval($product->initial_stock / $perLayer);
            }

            DB::table('inventories')->insert([
                'product_id' => $product->id,
                'quantity' => $product->initial_stock,
                'box' => $box,
                'pallet' => $pallet,
                'layer' => $layer,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. حذف ستون initial_stock از جدول products
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('initial_stock');
        });
    }

    public function down(): void
    {
        // بازگرداندن ستون initial_stock به products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('initial_stock', 15, 2)->default(0);
        });

        // بازگرداندن داده‌ها (در صورت امکان)
        $inventories = DB::table('inventories')->get();
        foreach ($inventories as $inv) {
            DB::table('products')
                ->where('id', $inv->product_id)
                ->update(['initial_stock' => $inv->quantity]);
        }

        Schema::dropIfExists('inventories');
    }
};