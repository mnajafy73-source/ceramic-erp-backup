<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        $('.datepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValue: true,
            autoClose: true,
            todayButton: true,
            initialValueType: 'persian',
            observer: true,
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت پخت شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('shuttle.index')); ?>">پخت شاتل</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="<?php echo e(route('shuttle.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control datepicker <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                           value="<?php echo e(old('date', $yesterday ?? jdate()->format('Y/m/d'))); ?>" required>
                    <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نوع کوره <span class="text-danger">*</span></label>
                    <select name="kiln_type" class="form-select <?php $__errorArgs = ['kiln_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب...</option>
                        <option value="kiln_1" <?php echo e(old('kiln_type') == 'kiln_1' ? 'selected' : ''); ?>>کوره ۱</option>
                        <option value="kiln_2" <?php echo e(old('kiln_type') == 'kiln_2' ? 'selected' : ''); ?>>کوره ۲</option>
                        <option value="kiln_3" <?php echo e(old('kiln_type') == 'kiln_3' ? 'selected' : ''); ?>>کوره ۳</option>
                        <option value="packaging" <?php echo e(old('kiln_type') == 'packaging' ? 'selected' : ''); ?>>بسته‌بندی</option>
                    </select>
                    <?php $__errorArgs = ['kiln_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4 mb-3" id="firing_subtype_container" style="display: none;">
                    <label class="form-label">نوع پخت <span class="text-danger">*</span></label>
                    <select name="firing_subtype" class="form-select <?php $__errorArgs = ['firing_subtype'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">انتخاب...</option>
                        <option value="mum" <?php echo e(old('firing_subtype') == 'mum' ? 'selected' : ''); ?>>موم (۹۰۰ درجه)</option>
                        <option value="glaze" <?php echo e(old('firing_subtype') == 'glaze' ? 'selected' : ''); ?>>لعاب‌دار</option>
                    </select>
                    <?php $__errorArgs = ['firing_subtype'];
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
                    <div id="products-container">
                        <div class="product-row row g-2 mb-2">
                            <div class="col-md-4">
                                <select name="products[0][product_id]" class="form-select" required>
                                    <option value="">انتخاب محصول...</option>
                                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($product->id); ?>" <?php echo e(old('products.0.product_id') == $product->id ? 'selected' : ''); ?>><?php echo e($product->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="products[0][output_quantity]" class="form-control" placeholder="تعداد خروجی" step="1" value="<?php echo e(old('products.0.output_quantity')); ?>">
                            </div>
                            <div class="col-md-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="products[0][is_packaged]" value="0">
                                    <input class="form-check-input" type="checkbox" name="products[0][is_packaged]" value="1" <?php echo e(old('products.0.is_packaged') ? 'checked' : ''); ?>>
                                    <label class="form-check-label">بسته‌بندی</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm remove-product w-100">-</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-product" class="btn btn-success mt-2">
                        <i class="fas fa-plus me-1"></i> افزودن محصول
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="<?php echo e(route('shuttle.index')); ?>" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>

<script>
    let productCount = 1;

    document.getElementById('add-product').addEventListener('click', function() {
        const container = document.getElementById('products-container');
        const newRow = document.createElement('div');
        newRow.className = 'product-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-4">
                <select name="products[${productCount}][product_id]" class="form-select" required>
                    <option value="">انتخاب محصول...</option>
                    <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="products[${productCount}][output_quantity]" class="form-control" placeholder="تعداد خروجی" step="1">
            </div>
            <div class="col-md-3 d-flex align-items-center">
                <div class="form-check form-switch">
                    <input type="hidden" name="products[${productCount}][is_packaged]" value="0">
                    <input class="form-check-input" type="checkbox" name="products[${productCount}][is_packaged]" value="1">
                    <label class="form-check-label">بسته‌بندی</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-product w-100">-</button>
            </div>
        `;
        container.appendChild(newRow);
        productCount++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-product')) {
            const row = e.target.closest('.product-row');
            if (document.querySelectorAll('.product-row').length > 1) {
                row.remove();
            } else {
                alert('حداقل یک محصول باید وجود داشته باشد.');
            }
        }
    });

    // نمایش/مخفی کردن نوع پخت برای کوره ۳
    document.querySelector('select[name="kiln_type"]').addEventListener('change', function() {
        const container = document.getElementById('firing_subtype_container');
        if (this.value === 'kiln_3') {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/shuttle/create.blade.php ENDPATH**/ ?>