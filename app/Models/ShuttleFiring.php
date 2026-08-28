<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Morilog\Jalali\Jalalian;

class ShuttleFiring extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'kiln_type',
        'firing_subtype',
        'product_id',
        'output_quantity',
        'firing_number',
        'is_packaged',
        'year',
        'month',
        'day',
    ];

    protected $casts = [
        'date' => 'date',
        'is_packaged' => 'boolean',
    ];

    protected $appends = ['jalali_date'];

    public function getJalaliDateAttribute()
    {
        try {
            return Jalalian::fromCarbon($this->date)->format('Y/m/d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * محاسبه موجودی خام
     * فقط ورودی تونلی از موجودی خام کم می‌شود (برای بلسن)
     */
    public static function getRawStock($productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }

        $production = Production::where('product_id', $productId)
            ->whereNotNull('press_id')
            ->sum('quantity');

        $tonneliInput = TonneliFiringItem::where('product_id', $productId)
            ->sum('input_quantity');

        // خروجی شاتل (فقط برای محصولات غیر از بلسن)
        $shuttleOutput = 0;
        if ($product->name !== 'بلسن') {
            $shuttleOutput = self::where('product_id', $productId)
                ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
                ->sum('output_quantity');
        }

        // خروجی فرزندان
        $childOutput = 0;
        foreach ($product->children as $child) {
            $childOutput += TonneliFiringItem::where('product_id', $child->id)
                ->where('input_quantity', '>', 0)
                ->sum('input_quantity');

            if ($child->name !== 'بلسن') {
                $childOutput += self::where('product_id', $child->id)
                    ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
                    ->sum('output_quantity');
            }
        }

        return $production - $tonneliInput - $shuttleOutput - $childOutput;
    }

    /**
     * محاسبه موجودی موم (۹۰۰ درجه)
     */
    public static function getMumStock($productId)
    {
        $mumProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_3')
            ->where('firing_subtype', 'mum')
            ->sum('output_quantity');

        $glazeProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->sum('output_quantity');

        return $mumProduction - $glazeProduction;
    }

    /**
     * محاسبه موجودی ۱۳۰۰ درجه
     */
    public static function getGlaze1300Stock($productId)
    {
        $glazeProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->sum('output_quantity');

        $packagedShuttle = self::where('product_id', $productId)
            ->where('kiln_type', 'packaging')
            ->sum('output_quantity');

        return $glazeProduction - $packagedShuttle;
    }

    /**
     * محاسبه موجودی انبار
     * ✅ خروجی‌های تونلی بسته‌بندی‌شده + خروجی‌های شاتل بسته‌بندی‌شده
     * ✅ بدون نیاز به رکوردهای packaging اضافی در shuttle_firings
     */
    public static function getWarehouseStock($productId)
    {
        $opening = OpeningInventory::where('product_id', $productId)->sum('quantity');

        // خروجی‌های بسته‌بندی‌شده از کوره شاتل
        $packagedShuttle = self::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        // ✅ خروجی‌های بسته‌بندی‌شده از کوره تونلی (مستقیماً از tonneli_firing_items)
        $packagedTonneli = TonneliFiringItem::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        // فروش
        $sales = Sale::where('product_id', $productId)->sum('quantity');
        $informalSales = InformalSaleProduct::where('product_id', $productId)->sum('quantity');

        return $opening + $packagedShuttle + $packagedTonneli - $sales - $informalSales;
    }
}