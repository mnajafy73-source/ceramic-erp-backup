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
     * محاسبه موجودی خام یک محصول خاص
     */
    public static function getRawStock($productId)
    {
        $production = Production::where('product_id', $productId)
            ->whereNotNull('press_id')
            ->sum('quantity');

        $tonneliInput = TonneliFiringItem::where('product_id', $productId)
            ->sum('input_quantity');

        $shuttleInput = self::where('product_id', $productId)
            ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
            ->sum('output_quantity');

        return $production - $tonneliInput - $shuttleInput;
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

        $packaged = self::where('product_id', $productId)
            ->where('kiln_type', 'packaging')
            ->sum('output_quantity');

        return $glazeProduction - $packaged;
    }

    /**
     * محاسبه موجودی انبار
     */
    public static function getWarehouseStock($productId)
    {
        $opening = OpeningInventory::where('product_id', $productId)->sum('quantity');

        $packaged = self::where('product_id', $productId)
            ->where('kiln_type', 'packaging')
            ->sum('output_quantity');

        $sales = Sale::where('product_id', $productId)->sum('quantity');
        $informalSales = InformalSale::where('product_id', $productId)->sum('quantity');

        return $opening + $packaged - $sales - $informalSales;
    }
}