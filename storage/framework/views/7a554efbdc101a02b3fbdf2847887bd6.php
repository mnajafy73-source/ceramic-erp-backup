<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش تولید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('productions.index')); ?>">تولید</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
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

        <form action="<?php echo e(route('productions.update', $production)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <div class="row g-3">
                <!-- تاریخ -->
                <div class="col-md-4">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('date', $production->jalali_date)); ?>" required>
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

                <!-- اپراتور -->
                <div class="col-md-4">
                    <label class="form-label">اپراتور <span class="text-danger">*</span></label>
                    <select name="operator_id" class="form-select <?php $__errorArgs = ['operator_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب اپراتور</option>
                        <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operator): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($operator->id); ?>" <?php echo e(old('operator_id', $production->operator_id) == $operator->id ? 'selected' : ''); ?>>
                                <?php echo e($operator->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['operator_id'];
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

                <!-- پرس -->
                <div class="col-md-4">
                    <label class="form-label">پرس</label>
                    <select name="press_id" class="form-select <?php $__errorArgs = ['press_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <option value="">بدون پرس</option>
                        <?php $__currentLoopData = $presses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $press): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($press->id); ?>" <?php echo e(old('press_id', $production->press_id) == $press->id ? 'selected' : ''); ?>>
                                <?php echo e($press->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['press_id'];
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
                <div class="col-md-4">
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
                            <option value="<?php echo e($product->id); ?>" <?php echo e(old('product_id', $production->product_id) == $product->id ? 'selected' : ''); ?>>
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

                <!-- عملیات (فارسی) -->
                <div class="col-md-4">
                    <label class="form-label">عملیات <span class="text-danger">*</span></label>
                    <select name="stage" class="form-select <?php $__errorArgs = ['stage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                        <option value="">انتخاب عملیات</option>
                        <option value="تولید" <?php echo e(old('stage', $production->stage) == 'تولید' ? 'selected' : ''); ?>>تولید</option>
                        <option value="پرداخت" <?php echo e(old('stage', $production->stage) == 'پرداخت' ? 'selected' : ''); ?>>پرداخت</option>
                        <option value="بسته‌بندی" <?php echo e(old('stage', $production->stage) == 'بسته‌بندی' ? 'selected' : ''); ?>>بسته‌بندی</option>
                    </select>
                    <?php $__errorArgs = ['stage'];
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

                <!-- تعداد -->
                <div class="col-md-4">
                    <label class="form-label">تعداد <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control <?php $__errorArgs = ['quantity'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('quantity', $production->quantity)); ?>" required min="1">
                    <?php $__errorArgs = ['quantity'];
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

                <!-- زمان (ساعت) -->
                <div class="col-md-4">
                    <label class="form-label">زمان (ساعت)</label>
                    <input type="number" name="time_hours" class="form-control <?php $__errorArgs = ['time_hours'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('time_hours', $production->time_hours)); ?>" step="0.1" min="0">
                    <?php $__errorArgs = ['time_hours'];
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

                <!-- یادداشت -->
                <div class="col-12">
                    <label class="form-label">یادداشت</label>
                    <textarea name="notes" class="form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="2"><?php echo e(old('notes', $production->notes)); ?></textarea>
                    <?php $__errorArgs = ['notes'];
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

                <!-- توقف‌ها (فارسی) -->
                <div class="col-12 mt-3">
                    <hr>
                    <h6 class="fw-bold">توقف‌ها (اختیاری)</h6>
                    <div id="stops-container">
                        <?php if($production->stops->count()): ?>
                            <?php $__currentLoopData = $production->stops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="row g-2 stop-row mt-2">
                                    <div class="col-md-5">
                                        <select name="stop_types[]" class="form-select">
                                            <option value="خرابی ماشین" <?php echo e($stop->type == 'خرابی ماشین' ? 'selected' : ''); ?>>خرابی ماشین</option>
                                            <option value="تعویض قالب" <?php echo e($stop->type == 'تعویض قالب' ? 'selected' : ''); ?>>تعویض قالب</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0" value="<?php echo e($stop->hours); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-stop">حذف</button>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <div class="row g-2 stop-row">
                                <div class="col-md-5">
                                    <select name="stop_types[]" class="form-select">
                                        <option value="خرابی ماشین">خرابی ماشین</option>
                                        <option value="تعویض قالب">تعویض قالب</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-stop" style="display:none;">حذف</button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-stop" class="btn btn-sm btn-secondary mt-2">➕ افزودن توقف</button>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">به‌روزرسانی</button>
                <a href="<?php echo e(route('productions.index')); ?>" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let stopIndex = <?php echo e($production->stops->count() ?: 1); ?>;

        document.getElementById('add-stop').addEventListener('click', function() {
            const container = document.getElementById('stops-container');
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 stop-row mt-2';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <select name="stop_types[]" class="form-select">
                        <option value="خرابی ماشین">خرابی ماشین</option>
                        <option value="تعویض قالب">تعویض قالب</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-stop">حذف</button>
                </div>
            `;
            container.appendChild(newRow);
            stopIndex++;
        });

        document.getElementById('stops-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-stop')) {
                const row = e.target.closest('.stop-row');
                if (document.querySelectorAll('.stop-row').length > 1) {
                    row.remove();
                } else {
                    alert('حداقل یک ردیف توقف باید باقی بماند.');
                }
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/productions/edit.blade.php ENDPATH**/ ?>