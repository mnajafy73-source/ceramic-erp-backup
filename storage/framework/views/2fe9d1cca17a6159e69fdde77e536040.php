<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let itemIndex = <?php echo e(isset($tonneli) ? $tonneli->items->count() : 0); ?>;

    function addItemRow(productId = '', inputQty = '', outputQty = '', isPackaged = false) {
        const container = document.getElementById('items-container');
        if (!container) return;
        const checked = isPackaged ? 'checked' : '';
        const html = `
            <div class="row g-2 mb-2 item-row" id="item-row-${itemIndex}">
                <div class="col-md-3">
                    <select name="items[${itemIndex}][product_id]" class="form-select product-select" required>
                        <option value="">انتخاب محصول...</option>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>" ${productId == <?php echo e($p->id); ?> ? 'selected' : ''}><?php echo e($p->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[${itemIndex}][input_quantity]" class="form-control" placeholder="ورودی" step="0.01" value="${inputQty}">
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[${itemIndex}][output_quantity]" class="form-control" placeholder="خروجی" step="0.01" value="${outputQty}">
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input type="hidden" name="items[${itemIndex}][is_packaged]" value="0">
                        <input class="form-check-input" type="checkbox" name="items[${itemIndex}][is_packaged]" value="1" ${checked}>
                        <label class="form-check-label">بسته‌بندی</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.item-row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);

        $(`#item-row-${itemIndex} .product-select`).select2({
            theme: 'bootstrap-5',
            placeholder: 'جستجوی محصول...',
            allowClear: true,
            language: 'fa'
        });

        itemIndex++;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // راه‌اندازی Select2 برای ردیف‌های موجود
        $('.product-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'جستجوی محصول...',
            allowClear: true,
            language: 'fa'
        });

        // راه‌اندازی Datepicker شمسی
        try {
            if (typeof $ !== 'undefined' && $.fn.persianDatepicker) {
                $('#date').persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    initialValue: true,
                    initialValueType: 'persian',
                    observer: true,
                    calendar: { persian: { locale: 'fa' } }
                });
            }
        } catch (e) {
            console.log('Datepicker not loaded');
        }

        // اگر ردیفی وجود ندارد، یک ردیف پیش‌فرض اضافه کن
        if (document.querySelectorAll('.item-row').length === 0) {
            addItemRow();
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت پخت تونلی جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('tonneli.index')); ?>">پخت‌های تونلی</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('tonneli.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                           value="<?php echo e(old('date', $yesterday ?? '')); ?>" required autocomplete="off">
                    <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">➕ محصولات این پخت</h6>
                    <div id="items-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow()">
                        <i class="fas fa-plus-circle"></i> افزودن محصول
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="<?php echo e(route('tonneli.index')); ?>" class="btn btn-secondary ms-2">بازگشت</a>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/tonneli/create.blade.php ENDPATH**/ ?>