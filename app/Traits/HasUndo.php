<?php

namespace App\Traits;

trait HasUndo
{
    public static function bootHasUndo()
    {
        static::deleting(function ($model) {
            // فقط داده‌های ضروری را ذخیره کن
            $data = $model->getAttributes();
            unset($data['id'], $data['created_at'], $data['updated_at']);

            // به‌جای flash از put استفاده کن تا برای درخواست بعدی هم بماند
            session()->put('undo_record', [
                'class' => get_class($model),
                'data'   => $data,
            ]);
        });
    }
}