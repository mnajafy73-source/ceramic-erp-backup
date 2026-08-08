<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormulaItem extends Model
{
    use HasFactory;

    protected $fillable = ['formula_id', 'raw_material_id', 'percentage'];

    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}