

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
    <h4 class="fw-bold mb-1">ثبت خرید کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('packaging-purchases.index')); ?>">خرید کارتن و لایه</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="<?php echo e(route('packaging-purchases.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ خرید <span class="text-danger">*</span></label>
                    <input type="text" name="purchase_date" class="form-control datepicker <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                           value="<?php echo e(old('purchase_date', jdate()->format('Y/m/d'))); ?>" required>
                    <?php $__errorArgs = ['purchase_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تأمین‌کننده</label>
                    <input type="text" name="supplier" class="form-control" value="<?php echo e(old('supplier')); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">هزینه حمل‌ونقل (ریال)</label>
                    <input type="text" name="total_transport_cost" class="form-control format-number" value="<?php echo e(old('total_transport_cost', 0)); ?>">
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label">اقلام خریداری‌شده <span class="text-danger">*</span></label>
                <div id="items-container">
                    <div class="item-row row g-2 mb-2">
                        <div class="col-md-4">
                            <select name="items[0][packaging_id]" class="form-select" required>
                                <option value="">انتخاب کارتن/لایه...</option>
                                <?php $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($packaging->id); ?>">
                                        <?php echo e($packaging->name); ?> (<?php echo e($packaging->type == 'carton' ? 'کارتن' : 'لایه'); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="items[0][quantity]" placeholder="تعداد" class="form-control format-number" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="items[0][total_price]" placeholder="قیمت کل (ریال)" class="form-control format-number" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-item w-100">-</button>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-item" class="btn btn-success mt-2">
                    <i class="fas fa-plus me-1"></i> افزودن قلم
                </button>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت خرید</button>
            <a href="<?php echo e(route('packaging-purchases.index')); ?>" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>

<script>
    let itemCount = 1;
    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const newRow = document.createElement('div');
        newRow.className = 'item-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-4">
                <select name="items[${itemCount}][packaging_id]" class="form-select" required>
                    <option value="">انتخاب کارتن/لایه...</option>
                    <?php $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($packaging->id); ?>">
                            <?php echo e($packaging->name); ?> (<?php echo e($packaging->type == 'carton' ? 'کارتن' : 'لایه'); ?>)
                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="items[${itemCount}][quantity]" placeholder="تعداد" class="form-control format-number" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="items[${itemCount}][total_price]" placeholder="قیمت کل (ریال)" class="form-control format-number" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item w-100">-</button>
            </div>
        `;
        container.appendChild(newRow);
        itemCount++;

        if (typeof window.applyFormatToNewInputs === 'function') {
            window.applyFormatToNewInputs(container);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('حداقل یک قلم باید وجود داشته باشد.');
            }
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/packaging-purchases/create.blade.php ENDPATH**/ ?>