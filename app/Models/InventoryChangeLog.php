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
        'source',
        'description',
        'user_id',
    ];

    protected $casts = [
        'old_value' => 'decimal:2',
        'new_value' => 'decimal:2',
    ];

    // ═══════════════════════════════════════════════════════════
    //  ✅ برچسب‌های فارسی برای منبع تغییر
    // ═══════════════════════════════════════════════════════════
    public const SOURCE_LABELS = [
        // ─── ویرایش دستی ───
        'manual_warehouse'         => 'ویرایش دستی موجودی انبار',
        'manual_warehouse_set'     => 'تنظیم دستی موجودی انبار',
        'manual_warehouse_adjust'  => 'کسر/اضافه دستی موجودی انبار',
        'manual_raw'               => 'ویرایش دستی موجودی خام',
        'manual_wax'               => 'ویرایش دستی موجودی موم',
        'manual_shoulder'          => 'ویرایش دستی موجودی شانه شده',
        'manual_waste_mum'         => 'ویرایش دستی ضایعات موم',
        'manual_glaze1300'         => 'ویرایش دستی موجودی ۱۳۰۰',
        'manual_unpackaged'        => 'ویرایش دستی موجودی بسته‌نشده',
        'manual_settings'          => 'تنظیمات دستی موجودی',
        'manual_raw_material'      => 'ویرایش دستی مواد اولیه',
        'manual_raw_material_set'  => 'تنظیم دستی مواد اولیه',
        'manual_raw_material_adjust'=> 'کسر/اضافه دستی مواد اولیه',
        'manual_packaging'         => 'ویرایش دستی کارتن/لایه',
        'manual_packaging_set'     => 'تنظیم دستی کارتن/لایه',
        'manual_packaging_adjust'  => 'کسر/اضافه دستی کارتن/لایه',
        'manual_reset_all'         => 'صفر کردن همه موجودی‌ها',

        // ─── ایمپورت ───
        'import_warehouse'          => 'ایمپورت اکسل - موجودی انبار',
        'import_raw'                => 'ایمپورت اکسل - موجودی خام',
        'import_glaze1300'          => 'ایمپورت اکسل - موجودی ۱۳۰۰',
        'import_shoulder'           => 'ایمپورت اکسل - موجودی شانه شده',
        'import_waste_mum'          => 'ایمپورت اکسل - ضایعات موم',
        'import_wax'                => 'ایمپورت اکسل - موجودی موم',
        'import_raw_material'       => 'ایمپورت اکسل - مواد اولیه',
        'import_material_making'    => 'ایمپورت اکسل - مواد سازی',
        'import_packaging'          => 'ایمپورت اکسل - کارتن و لایه',
        'import_packaging_consumed' => 'ایمپورت اکسل - مصرف بسته‌بندی',
        'import_production'         => 'ایمپورت اکسل - ثبت تولید',
        'import_tonneli_packaged'   => 'ایمپورت اکسل - پخت تونلی (بسته‌بندی)',
        'import_tonneli_input'      => 'ایمپورت اکسل - ورودی کوره تونلی',
        'import_shuttle_output'     => 'ایمپورت اکسل - خروجی کوره شاتل',
        'import_shuttle_k1'         => 'ایمپورت اکسل - پخت کوره ۱',
        'import_shuttle_k2'         => 'ایمپورت اکسل - پخت کوره ۲ (۱۳۰۰)',
        'import_shuttle_k2_output'  => 'ایمپورت اکسل - خروجی کوره ۲ (۱۳۰۰)',
        'import_shuttle_k3_mum'     => 'ایمپورت اکسل - پخت کوره ۳ (موم)',
        'import_shuttle_k4'         => 'ایمپورت اکسل - پخت کوره ۴',
        'import_packaging_from_k2'  => 'ایمپورت اکسل - بسته‌بندی از کوره ۲',
        'import_packaging_from_k4'  => 'ایمپورت اکسل - بسته‌بندی از کوره ۴',
        'import_sale_formal'        => 'ایمپورت اکسل - فروش رسمی',
        'import_sale_informal'      => 'ایمپورت اکسل - فروش غیررسمی',

        // ─── تولید ───
        'production'          => 'ثبت تولید',

        // ─── کوره تونلی (دستی) ───
        'tonneli_input'              => 'ورودی کوره تونلی',
        'tonneli_input_return'       => 'برگشت ورودی کوره تونلی',
        'tonneli_packaged'           => 'پخت تونلی - بسته‌بندی‌شده',
        'tonneli_packaged_return'    => 'برگشت بسته‌بندی تونلی',
        'tonneli_packaging_consumed' => 'مصرف بسته‌بندی (پخت تونلی)',
        'tonneli_packaging_return'   => 'برگشت بسته‌بندی (تونلی)',
        'tonneli_output'             => 'خروجی کوره تونلی',

        // ─── کوره شاتل ───
        'shuttle_kiln_1'       => 'پخت کوره ۱',
        'shuttle_kiln_2'       => 'پخت کوره ۲ (۱۳۰۰)',
        'shuttle_kiln_3_glaze' => 'پخت کوره ۳ (لعاب)',
        'shuttle_kiln_3_mum'   => 'پخت کوره ۳ (موم)',
        'shuttle_kiln_4'       => 'پخت کوره ۴',
        'shuttle_packaging'    => 'بسته‌بندی',

        // ─── فروش ───
        'sale_formal'          => 'فروش رسمی',
        'sale_informal'        => 'فروش غیررسمی',
        'sale_formal_return'   => 'برگشت فروش رسمی',
        'sale_informal_return' => 'برگشت فروش غیررسمی',

        // ─── شانه/ضایعات ───
        'shoulder_record'  => 'شانه زنی',
        'waste_mum_record' => 'ضایعات موم',
    ];

    public function getSourceLabelAttribute(): ?string
    {
        if (!$this->source) return null;
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ برچسب فارسی برای نوع موجودی
    // ═══════════════════════════════════════════════════════════
    public function getInventoryTypeLabelAttribute(): string
    {
        $type = $this->loggable_type;

        $labels = [
            'App\Models\WarehouseInventory'   => 'موجودی انبار',
            'App\Models\RawInventory'         => 'موجودی خام',
            'App\Models\WaxInventory'         => 'موجودی موم',
            'App\Models\ShoulderInventory'    => 'موجودی شانه شده',
            'App\Models\WasteMumInventory'    => 'ضایعات موم',
            'App\Models\Glaze1300Inventory'   => 'موجودی ۱۳۰۰ درجه',
            'App\Models\RawMaterial'          => 'مواد اولیه',
            'App\Models\Packaging'            => 'کارتن / لایه',
            'App\Models\Product'              => 'محصول',
        ];

        return $labels[$type] ?? class_basename($type);
    }

    public function getInventoryTypeBadgeAttribute(): string
    {
        return match ($this->loggable_type) {
            'App\Models\WarehouseInventory'  => 'bg-primary',
            'App\Models\RawInventory'        => 'bg-warning text-dark',
            'App\Models\WaxInventory'        => 'bg-danger',
            'App\Models\ShoulderInventory'   => 'bg-info text-dark',
            'App\Models\WasteMumInventory'   => 'bg-secondary',
            'App\Models\Glaze1300Inventory'  => 'bg-dark',
            'App\Models\RawMaterial'         => 'bg-success',
            'App\Models\Packaging'           => 'bg-info',
            'App\Models\Product'             => 'bg-primary',
            default                          => 'bg-light text-dark',
        };
    }

    // ═══════════════════════════════════════════════════════════
    //  ✅ اسم کالا
    // ═══════════════════════════════════════════════════════════
    public function getSubjectNameAttribute(): ?string
    {
        static $cache = [];
        $cacheKey = $this->loggable_type . ':' . $this->loggable_id;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $name = null;

        $productBasedTypes = [
            'App\Models\WarehouseInventory',
            'App\Models\RawInventory',
            'App\Models\WaxInventory',
            'App\Models\ShoulderInventory',
            'App\Models\WasteMumInventory',
            'App\Models\Glaze1300Inventory',
        ];

        if (in_array($this->loggable_type, $productBasedTypes)) {
            $product = Product::find($this->loggable_id);
            $name = $product ? $product->name : 'کالای حذف‌شده (#' . $this->loggable_id . ')';
        }
        elseif ($this->loggable_type === 'App\Models\Product') {
            $product = Product::find($this->loggable_id);
            $name = $product ? $product->name : 'کالای حذف‌شده (#' . $this->loggable_id . ')';
        }
        elseif ($this->loggable_type === 'App\Models\RawMaterial') {
            $material = \App\Models\RawMaterial::find($this->loggable_id);
            $name = $material ? $material->name : 'ماده حذف‌شده (#' . $this->loggable_id . ')';
        }
        elseif ($this->loggable_type === 'App\Models\Packaging') {
            $packaging = \App\Models\Packaging::find($this->loggable_id);
            $name = $packaging
                ? (($packaging->type == 'carton' ? '[کارتن] ' : '[لایه] ') . $packaging->name)
                : 'بسته حذف‌شده (#' . $this->loggable_id . ')';
        }
        else {
            try {
                $loggable = $this->loggable;
                if ($loggable && isset($loggable->name)) {
                    $name = $loggable->name;
                }
            } catch (\Exception $e) {
                $name = '#' . $this->loggable_id;
            }
        }

        $cache[$cacheKey] = $name;
        return $name;
    }

    public function getDeltaAttribute(): float
    {
        return (float) $this->new_value - (float) $this->old_value;
    }

    public function getDeltaLabelAttribute(): string
    {
        $delta = $this->delta;
        if ($delta > 0) return '+' . number_format($delta);
        if ($delta < 0) return number_format($delta);
        return '0';
    }

    public function getDeltaColorAttribute(): string
    {
        $delta = $this->delta;
        if ($delta > 0) return 'success';
        if ($delta < 0) return 'danger';
        return 'secondary';
    }

    public function loggable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        $model,
        $field,
        $oldValue,
        $newValue,
        $mode = 'set',
        $customId = null,
        $source = null,
        $description = null
    ) {
        return self::create([
            'loggable_type' => get_class($model),
            'loggable_id'   => $customId ?? $model->id,
            'field'         => $field,
            'old_value'     => $oldValue,
            'new_value'     => $newValue,
            'mode'          => $mode,
            'source'        => $source,
            'description'   => $description,
            'user_id'       => auth()->id(),
        ]);
    }
}