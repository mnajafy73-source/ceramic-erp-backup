<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش پخت شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('shuttle.index')); ?>">کوره شاتل</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<?php if($firing->is_imported): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        این رکورد از <strong>اکسل</strong> ایمپورت شده. با ذخیره تغییرات، به‌عنوان <strong>دستی</strong> ثبت می‌شه.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="<?php echo e(route('shuttle.update', [
            'year' => $firing->year,
            'month' => $firing->month,
            'day' => $firing->day,
            'kiln_type' => $firing->kiln_type,
            'firingNumber' => $firing->firing_number,
            'itemId' => $firing->id,
        ])); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="row g-3">
                <!-- تاریخ -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('date', $firing->jalali_date)); ?>" required>
                    <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- کوره -->
                <div class="col-md-3">
                    <label class="form-label">کوره <span class="text-danger">*</span></label>
                    <select name="kiln_number" class="form-select <?php $__errorArgs = ['kiln_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب کوره</option>
                        <option value="1" <?php echo e(old('kiln_number', $firing->kiln_number) == '1' ? 'selected' : ''); ?>>کوره ۱</option>
                        <option value="2" <?php echo e(old('kiln_number', $firing->kiln_number) == '2' ? 'selected' : ''); ?>>کوره ۲</option>
                        <option value="3" <?php echo e(old('kiln_number', $firing->kiln_number) == '3' ? 'selected' : ''); ?>>کوره ۳</option>
                        <option value="4" <?php echo e(old('kiln_number', $firing->kiln_number) == '4' ? 'selected' : ''); ?>>کوره ۴</option>
                        <option value="packaging" <?php echo e(old('kiln_number', $firing->kiln_number) == 'packaging' ? 'selected' : ''); ?>>بسته‌بندی</option>
                    </select>
                    <?php $__errorArgs = ['kiln_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- نوع پخت -->
                <div class="col-md-3">
                    <label class="form-label">نوع پخت <span class="text-danger">*</span></label>
                    <select name="firing_type" class="form-select <?php $__errorArgs = ['firing_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب نوع پخت</option>
                        <option value="معمولی" <?php echo e(old('firing_type', $firing->firing_type) == 'معمولی' ? 'selected' : ''); ?>>معمولی</option>
                        <option value="1300" <?php echo e(old('firing_type', $firing->firing_type) == '1300' ? 'selected' : ''); ?>>۱۳۰۰</option>
                        <option value="لعابدار" <?php echo e(old('firing_type', $firing->firing_type) == 'لعابدار' ? 'selected' : ''); ?>>لعابدار</option>
                        <option value="موم" <?php echo e(old('firing_type', $firing->firing_type) == 'موم' ? 'selected' : ''); ?>>موم</option>
                    </select>
                    <?php $__errorArgs = ['firing_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- محصول -->
                <div class="col-md-3">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب محصول</option>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($product->id); ?>" <?php echo e(old('product_id', $firing->product_id) == $product->id ? 'selected' : ''); ?>>
                                <?php echo e($product->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- تعداد کل -->
                <div class="col-md-3">
                    <label class="form-label">تعداد کل <span class="text-danger">*</span></label>
                    <input type="number" name="total_quantity" class="form-control <?php $__errorArgs = ['total_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('total_quantity', $firing->total_quantity ?? 0)); ?>" step="1" min="0" required>
                    <?php $__errorArgs = ['total_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- تعداد اصلی -->
                <div class="col-md-3">
                    <label class="form-label">تعداد اصلی (خروجی سالم) <span class="text-danger">*</span></label>
                    <input type="number" name="main_quantity" class="form-control <?php $__errorArgs = ['main_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('main_quantity', $firing->output_quantity)); ?>" step="1" min="0" required>
                    <?php $__errorArgs = ['main_quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- ضایعات -->
                <div class="col-md-3">
                    <label class="form-label">ضایعات <span class="text-danger">*</span></label>
                    <input type="number" name="waste" class="form-control <?php $__errorArgs = ['waste'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('waste', 0)); ?>" step="1" min="0" required>
                    <?php $__errorArgs = ['waste'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <!-- بسته‌بندی -->
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_packaged" class="form-check-input" id="is_packaged" value="1"
                               <?php echo e(old('is_packaged', $firing->is_packaged) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="is_packaged">بسته‌بندی شده</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">به‌روزرسانی</button>
                <a href="<?php echo e(route('shuttle.show', [
                    'year' => $firing->year,
                    'month' => $firing->month,
                    'day' => $firing->day,
                    'kiln_type' => $firing->kiln_type,
                    'firingNumber' => $firing->firing_number,
                ])); ?>" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/shuttle/edit.blade.php ENDPATH**/ ?>