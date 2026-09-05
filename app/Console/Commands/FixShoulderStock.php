<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShoulderRecord;
use App\Models\ShoulderInventory;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixShoulderStock extends Command
{
    protected $signature = 'shoulder:fix-stock';
    protected $description = 'محاسبه موجودی شانه شده بر اساس موجودی اول دوره و رکوردهای شانه زنی';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه موجودی شانه شده...');

        // موجودی اول دوره شانه شده
        $openingStocks = [];
        $openingItems = OpeningInventory::with('product')->get();
        foreach ($openingItems as $item) {
            $openingStocks[$item->product_id] = $item->quantity;
        }

        // رکوردهای شانه زنی (مجموع total به‌ازای هر محصول)
        $records = ShoulderRecord::select('product_name', DB::raw('SUM(total) as total_stock'))
            ->groupBy('product_name')
            ->get();

        foreach ($records as $record) {
            $product = Product::where('name', $record->product_name)->first();
            if (!$product) continue;

            $opening = $openingStocks[$product->id] ?? 0;
            $stock = $opening + $record->total_stock;

            ShoulderInventory::updateOrCreate(
                ['product_id' => $product->id],
                ['stock' => max(0, $stock)]
            );

            $this->line("   {$product->name}: {$opening} + {$record->total_stock} = " . max(0, $stock));
        }

        $this->info('✅ موجودی شانه شده با موفقیت به‌روز شد.');
        return 0;
    }
}