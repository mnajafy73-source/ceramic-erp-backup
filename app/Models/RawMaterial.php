<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'stock', // این فیلد را می‌توانیم نگه داریم، اما برای محاسبه دقیق‌تر از متد استفاده می‌کنیم
    ];

    public function purchaseItems()
    {
        return $this->hasMany(RawMaterialPurchaseItem::class);
    }

    public function formulaItems()
    {
        return $this->hasMany(FormulaItem::class);
    }

    /**
     * محاسبه موجودی واقعی بر اساس خریدها و مصرف تولیدات
     */
    public function getActualStockAttribute()
    {
        // مجموع خریدهای این ماده (به گرم)
        $totalPurchased = $this->purchaseItems()->sum('quantity');

        // مجموع مصرف در تولیدات (بر اساس فرمول محصولات)
        $totalConsumed = DB::table('productions')
            ->join('products', 'productions.product_id', '=', 'products.id')
            ->join('formula_items', 'products.formula_id', '=', 'formula_items.formula_id')
            ->where('formula_items.raw_material_id', $this->id)
            ->selectRaw('SUM(productions.quantity * (products.weight / 1000) * (formula_items.percentage / 100) * 1000) as total_gram')
            ->value('total_gram') ?? 0;

        return $totalPurchased - $totalConsumed;
    }

    /**
     * به‌روزرسانی فیلد stock بر اساس محاسبه واقعی
     */
    public function refreshStock()
    {
        $this->stock = $this->actual_stock;
        $this->save();
        return $this->stock;
    }
}