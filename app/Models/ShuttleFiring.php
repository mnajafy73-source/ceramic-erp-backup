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
     * برای بلسن: تولید - ورودی تونلی - مصرف فرزندان
     * برای سایر محصولات: تولید - ورودی تونلی - خروجی شاتل - مصرف فرزندان
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

        if ($production == 0) {
            return 0;
        }

        $tonneliInput = TonneliFiringItem::where('product_id', $productId)
            ->sum('input_quantity');

        $shuttleOutput = 0;
        if ($product->name !== 'بلسن') {
            $shuttleOutput = self::where('product_id', $productId)
                ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
                ->sum('output_quantity');
        }

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
     * ✅ محاسبه موجودی انبار (اصلاح‌شده)
     * 
     * فرمول:
     * موجودی انبار = موجودی اول دوره + خروجی تونلی بسته‌بندی‌شده + خروجی شاتل بسته‌بندی‌شده - فروش رسمی - فروش غیررسمی
     */
    public static function getWarehouseStock($productId)
    {
        // ۱. موجودی اول دوره از جدول opening_inventories
        $opening = OpeningInventory::where('product_id', $productId)->sum('quantity');

        // ۲. خروجی‌های بسته‌بندی‌شده از کوره تونلی (is_packaged = 1)
        $packagedTonneli = TonneliFiringItem::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        // ۳. خروجی‌های بسته‌بندی‌شده از کوره شاتل (is_packaged = 1)
        $packagedShuttle = self::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        // ۴. فروش رسمی (از جدول sale_products)
        $sales = SaleProduct::where('product_id', $productId)->sum('quantity');

        // ۵. فروش غیررسمی (از جدول informal_sale_products)
        $informalSales = InformalSaleProduct::where('product_id', $productId)->sum('quantity');

        // ============================================================
        // محاسبه نهایی موجودی انبار
        // ============================================================
        return $opening + $packagedTonneli + $packagedShuttle - $sales - $informalSales;
    }
}