<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'loggable_type',
        'loggable_id',
        'field',
        'old_value',
        'new_value',
        'mode',
        'user_id',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
    ];

    public function loggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ پارامتر پنجم (customId) اضافه شد
    public static function log($model, $field, $oldValue, $newValue, $mode = 'set', $customId = null)
    {
        return self::create([
            'loggable_type' => get_class($model),
            'loggable_id'   => $customId ?? $model->id,
            'field'         => $field,
            'old_value'     => $oldValue,
            'new_value'     => $newValue,
            'mode'          => $mode,
            'user_id'       => auth()->id(),
        ]);
    }
}