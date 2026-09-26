<?php

namespace App\Observers;

use App\Models\Production;
use App\Helpers\ImportFlag;

class ProductionObserver
{
    /**
     * ⚠️ این Observer غیرفعال شده.
     *
     * دلیل: طبق منطق کسب‌وکار، ثبت تولید (چه دستی چه ایمپورت)
     * فقط باید موجودی خام (کالای نیم‌ساخته) رو زیاد کنه.
     * کم کردن مواد اولیه، کارتن و لایه از عهده‌ی
     * «مواد سازی» و «بسته‌بندی» هست، نه تولید.
     */

    public function created(Production $production)
    {
        // ✅ غیرفعال
    }

    public function updated(Production $production)
    {
        // ✅ غیرفعال
    }

    public function deleted(Production $production)
    {
        // ✅ غیرفعال
    }
}