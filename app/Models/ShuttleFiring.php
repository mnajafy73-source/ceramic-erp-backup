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
        'is_packaged',
        'firing_number',
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
        return Jalalian::fromCarbon($this->date)->format('Y/m/d');
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
        // تولید با پرس
        $production = Production::where('product_id', $productId)
            ->whereNotNull('press_id')
            ->sum('quantity');

        // ورودی تونلی
        $tonneliInput = TonneliFiring::where('product_id', $productId)
            ->sum('input_quantity') ?? 0;

        // فید پخت شاتل (کوره‌های ۱، ۲، ۳، ۴) - بسته‌بندی از خام کم نمی‌شود
        $shuttleFeed = self::where('product_id', $productId)
            ->whereIn('kiln_type', ['kiln_1', 'kiln_2', 'kiln_3', 'kiln_4'])
            ->sum('output_quantity');

        return $production - $tonneliInput - $shuttleFeed;
    }

    /**
     * محاسبه موجودی موم (۹۰۰ درجه) یک محصول خاص
     */
    public static function getMumStock($productId)
    {
        // پخت موم: شاتل کوره ۳ با نوع موم
        $mumProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_3')
            ->where('firing_subtype', 'mum')
            ->sum('output_quantity');

        // پخت ۱۳۰۰: شاتل کوره ۲
        $glazeProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->sum('output_quantity');

        return $mumProduction - $glazeProduction;
    }

    /**
     * محاسبه موجودی ۱۳۰۰ درجه یک محصول خاص
     */
    public static function getGlaze1300Stock($productId)
    {
        // پخت ۱۳۰۰: شاتل کوره ۲
        $glazeProduction = self::where('product_id', $productId)
            ->where('kiln_type', 'kiln_2')
            ->sum('output_quantity');

        // بسته‌بندی‌شده (kiln_type = 'packaging')
        $packaged = self::where('product_id', $productId)
            ->where('kiln_type', 'packaging')
            ->sum('output_quantity');

        return $glazeProduction - $packaged;
    }

    /**
     * محاسبه موجودی انبار یک محصول خاص
     */
    public static function getWarehouseStock($productId)
    {
        // موجودی اول دوره
        $opening = OpeningInventory::where('product_id', $productId)->sum('quantity');

        // بسته‌بندی‌شده (kiln_type = 'packaging')
        $packaged = self::where('product_id', $productId)
            ->where('kiln_type', 'packaging')
            ->sum('output_quantity');

        // فروش رسمی
        $sales = Sale::where('product_id', $productId)->sum('quantity');

        // فروش غیررسمی
        $informalSales = InformalSale::where('product_id', $productId)->sum('quantity');

        return $opening + $packaged - $sales - $informalSales;
    }
}