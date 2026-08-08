<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'stock',
    ];

    // رابطه با آیتم‌های خرید کارتن و لایه
    public function purchaseItems()
    {
        return $this->hasMany(PackagingPurchaseItem::class);
    }
}