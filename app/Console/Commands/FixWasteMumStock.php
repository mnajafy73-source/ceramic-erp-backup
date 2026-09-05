<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\WasteMumRecord;
use App\Models\WasteMumInventory;
use App\Models\OpeningInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixWasteMumStock extends Command
{
    protected $signature = 'wastemum:fix-stock';
    protected $description = 'محاسبه ضایعات موم بر اساس موجودی اول دوره و رکوردهای ضایعات موم';

    public function handle()
    {
        $this->info('🔄 شروع محاسبه ضایعات موم...');

        // موجودی اول دوره ضایعات موم
        $openingStocks = [];
        $openingItems = OpeningInventory::with('product')->get();
        foreach ($openingItems as $item) {
            $openingStocks[$item->product_id] = $item->quantity;
        }

        // رکوردهای ضایعات موم
        $records = WasteMumRecord::select('product_name', DB::raw('SUM(amount) as total_waste'))
            ->groupBy('product_name')
            ->get();

        foreach ($records as $record) {
            $product = Product::where('name', $record->product_name)->first();
            if (!$product) continue;

            $opening = $openingStocks[$product->id] ?? 0;
            $stock = $opening + $record->total_waste;

            WasteMumInventory::updateOrCreate(
                ['product_id' => $product->id],
                ['stock' => max(0, $stock)]
            );

            $this->line("   {$product->name}: {$opening} + {$record->total_waste} = " . max(0, $stock));
        }

        $this->info('✅ ضایعات موم با موفقیت به‌روز شد.');
        return 0;
    }
}