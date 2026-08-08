<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit',
        'stock',
    ];

    // رابطه با آیتم‌های خرید مواد اولیه
    public function purchaseItems()
    {
        return $this->hasMany(RawMaterialPurchaseItem::class);
    }

    public function formulaItems()
    {
        return $this->hasMany(FormulaItem::class);
    }
}