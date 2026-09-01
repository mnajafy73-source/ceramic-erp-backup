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
     * ✅ موجودی موم (۹۰۰ درجه) - فرمول نهایی
     * = خروجی شاتل موم - موجودی شانه شده - ضایعات موم
     */
    public static function getMumStock($productId)
    {
        // ۱. خروجی شاتل با نوع پخت موم
        $mumProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_3')
            ->where('firing_subtype', 'mum')
            ->sum('output_quantity');

        // ۲. موجودی شانه شده
        $shoulderStock = \App\Models\ShoulderInventory::where('product_id', $productId)->value('stock') ?? 0;

        // ۳. ضایعات موم
        $wasteMum = \App\Models\WasteMumInventory::where('product_id', $productId)->value('stock') ?? 0;

        return $mumProduction - $shoulderStock - $wasteMum;
    }

    /**
     * موجودی ۱۳۰۰ درجه
     */
    public static function getGlaze1300Stock($productId)
    {
        $glazeProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->sum('output_quantity');

        $packagedFromKiln2 = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        $packagedFromKiln4 = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_4')
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        return $glazeProduction - $packagedFromKiln2 - $packagedFromKiln4;
    }

    /**
     * موجودی انبار
     */
    public static function getWarehouseStock($productId)
    {
        $opening = OpeningInventory::where('product_id', $productId)->sum('quantity');

        $packagedTonneli = TonneliFiringItem::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->sum('output_quantity');

        $packagedShuttle = self::where('product_id', $productId)
            ->where('is_packaged', 1)
            ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_4'])
            ->sum('output_quantity');

        $sales = SaleProduct::where('product_id', $productId)->sum('quantity');

        $informalSales = InformalSaleProduct::where('product_id', $productId)->sum('quantity');

        return $opening + $packagedTonneli + $packagedShuttle - $sales - $informalSales;
    }
}