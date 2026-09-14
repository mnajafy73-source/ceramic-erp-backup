<?php $__env->startPush('styles'); ?>
<style>
    .form-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .form-section .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 2px solid #f1f3f5;
    }
    .form-section .section-header .section-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 15px;
        flex-shrink: 0;
    }
    .form-section .section-header h6 {
        margin: 0;
        font-weight: bold;
        font-size: 15px;
    }
    .form-section .section-header small {
        display: block;
        font-weight: normal;
        color: #6c757d;
        font-size: 12px;
        margin-top: 2px;
    }
    .icon-basic      { background: #0d6efd; }
    .icon-production { background: #fd7e14; }
    .icon-packaging  { background: #198754; }
    .icon-status     { background: #6f42c1; }
    .icon-alias      { background: #20c997; }

    .field-group {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px;
        border-right: 3px solid #dee2e6;
        height: 100%;
    }
    .field-group .field-group-title {
        font-size: 12px;
        font-weight: bold;
        color: #495057;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .field-group.carton-group { border-right-color: #198754; }
    .field-group.layer-group  { border-right-color: #0dcaf0; }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13,110,253,0.15);
    }
    .form-control[readonly] {
        background: #f1f3f5;
        cursor: not-allowed;
    }
    .checkbox-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px 16px;
        border: 1px solid #dee2e6;
        height: 100%;
        display: flex;
        align-items: center;
    }
    .checkbox-card .form-check {
        margin: 0;
    }
    .checkbox-card .form-check-label {
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .action-bar {
        background: #fff;
        border-radius: 12px;
        padding: 16px 24px;
        border: 1px solid #e9ecef;
        position: sticky;
        bottom: 12px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    }
    .current-alias-box {
        background: #e7f3ff;
        border-right: 3px solid #0d6efd;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        margin-top: 8px;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش کالا</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('products.index')); ?>">کالاها</a></li>
            <li class="breadcrumb-item active">ویرایش: <?php echo e($product->name); ?></li>
        </ol>
    </nav>
</div>

<form action="<?php echo e(route('products.update', $product)); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>

    
    
    
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-basic"><i class="fas fa-info-circle"></i></div>
            <div>
                <h6>اطلاعات پایه</h6>
                <small>اطلاعات اصلی و شناسایی کالا</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">کد <span class="text-danger">*</span></label>
                <input type="text" name="code"
                       class="form-control <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('code', $product->code)); ?>" readonly>
                <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <small class="text-muted">کد قابل ویرایش نیست</small>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">نام <span class="text-danger">*</span></label>
                <input type="text" name="name"
                       class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('name', $product->name)); ?>" required>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">واحد <span class="text-danger">*</span></label>
                <select name="unit_id" class="form-select <?php $__errorArgs = ['unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <option value="">انتخاب واحد</option>
                    <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($unit->id); ?>"
                            <?php echo e(old('unit_id', $product->unit_id) == $unit->id ? 'selected' : ''); ?>>
                            <?php echo e($unit->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['unit_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">وزن (گرم)</label>
                <input type="number" name="weight"
                       class="form-control <?php $__errorArgs = ['weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('weight', $product->weight)); ?>" step="0.01" min="0">
                <?php $__errorArgs = ['weight'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>

    
    
    
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-production"><i class="fas fa-industry"></i></div>
            <div>
                <h6>فرآیند تولید</h6>
                <small>مشخصات فنی و تولیدی محصول</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">نوع محصول</label>
                <select name="product_type" class="form-select <?php $__errorArgs = ['product_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="normal"
                        <?php echo e(old('product_type', $product->product_type ?? 'normal') == 'normal' ? 'selected' : ''); ?>>
                        معمولی
                    </option>
                    <option value="injection"
                        <?php echo e(old('product_type', $product->product_type ?? '') == 'injection' ? 'selected' : ''); ?>>
                        تزریق
                    </option>
                </select>
                <?php $__errorArgs = ['product_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">فرایند پخت</label>
                <select name="firing_process" class="form-select <?php $__errorArgs = ['firing_process'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="tonneli"
                        <?php echo e(old('firing_process', $product->firing_process) == 'tonneli' ? 'selected' : ''); ?>>
                        تونلی
                    </option>
                    <option value="shuttle"
                        <?php echo e(old('firing_process', $product->firing_process) == 'shuttle' ? 'selected' : ''); ?>>
                        شاتل
                    </option>
                    <option value="both"
                        <?php echo e(old('firing_process', $product->firing_process) == 'both' ? 'selected' : ''); ?>>
                        هر دو
                    </option>
                </select>
                <?php $__errorArgs = ['firing_process'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">تعداد حفره</label>
                <input type="number" name="cavities"
                       class="form-control <?php $__errorArgs = ['cavities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('cavities', $product->cavities)); ?>" min="1">
                <?php $__errorArgs = ['cavities'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">خوراک پخت تونلی</label>
                <input type="number" name="tonneli_feed_rate"
                       class="form-control <?php $__errorArgs = ['tonneli_feed_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('tonneli_feed_rate', $product->tonneli_feed_rate)); ?>" min="0">
                <?php $__errorArgs = ['tonneli_feed_rate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-6">
                <label class="form-label">فرمول</label>
                <select name="formula_id" class="form-select <?php $__errorArgs = ['formula_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="">بدون فرمول</option>
                    <?php $__currentLoopData = $formulas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $formula): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($formula->id); ?>"
                            <?php echo e(old('formula_id', $product->formula_id) == $formula->id ? 'selected' : ''); ?>>
                            <?php echo e($formula->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['formula_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6 col-lg-6">
                <label class="form-label">محصول خام (والد)</label>
                <select name="parent_product_id"
                        class="form-select <?php $__errorArgs = ['parent_product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <option value="">بدون والد (خام)</option>
                    <?php $__currentLoopData = $allProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($p->id); ?>"
                            <?php echo e(old('parent_product_id', $product->parent_product_id) == $p->id ? 'selected' : ''); ?>>
                            <?php echo e($p->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['parent_product_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php if($product->parent): ?>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        والد فعلی: <strong><?php echo e($product->parent->name); ?></strong>
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    
    
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-packaging"><i class="fas fa-box"></i></div>
            <div>
                <h6>بسته‌بندی</h6>
                <small>مشخصات کارتن، لایه، بسته و پالت</small>
            </div>
        </div>

        <div class="row g-3">
            
            <div class="col-md-6">
                <div class="field-group carton-group">
                    <div class="field-group-title">
                        <i class="fas fa-box text-success"></i>
                        کارتن
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">کارتن مصرفی</label>
                            <select name="carton_packaging_id"
                                    class="form-select <?php $__errorArgs = ['carton_packaging_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">انتخاب کارتن</option>
                                <?php $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($packaging->id); ?>"
                                        <?php echo e(old('carton_packaging_id', $product->carton_packaging_id) == $packaging->id ? 'selected' : ''); ?>>
                                        <?php echo e($packaging->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['carton_packaging_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">تعداد در کارتن</label>
                            <input type="number" name="per_box"
                                   class="form-control <?php $__errorArgs = ['per_box'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('per_box', $product->per_box)); ?>" min="0"
                                   placeholder="مثلاً ۱۲">
                            <?php $__errorArgs = ['per_box'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6">
                <div class="field-group layer-group">
                    <div class="field-group-title">
                        <i class="fas fa-layer-group text-info"></i>
                        لایه
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">لایه مصرفی</label>
                            <select name="layer_packaging_id"
                                    class="form-select <?php $__errorArgs = ['layer_packaging_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <option value="">انتخاب لایه</option>
                                <?php $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($packaging->id); ?>"
                                        <?php echo e(old('layer_packaging_id', $product->layer_packaging_id) == $packaging->id ? 'selected' : ''); ?>>
                                        <?php echo e($packaging->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['layer_packaging_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">تعداد لایه در کارتن</label>
                            <input type="number" name="layers_per_box"
                                   class="form-control <?php $__errorArgs = ['layers_per_box'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   value="<?php echo e(old('layers_per_box', $product->layers_per_box)); ?>" min="0"
                                   placeholder="مثلاً ۳">
                            <?php $__errorArgs = ['layers_per_box'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6">
                <label class="form-label">تعداد در بسته</label>
                <input type="number" name="per_pack"
                       class="form-control <?php $__errorArgs = ['per_pack'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('per_pack', $product->per_pack)); ?>" min="0">
                <?php $__errorArgs = ['per_pack'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="col-md-6">
                <label class="form-label">تعداد در پالت</label>
                <input type="number" name="per_pallet"
                       class="form-control <?php $__errorArgs = ['per_pallet'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('per_pallet', $product->per_pallet)); ?>" min="0">
                <?php $__errorArgs = ['per_pallet'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>
    </div>

    
    
    
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-status"><i class="fas fa-toggle-on"></i></div>
            <div>
                <h6>وضعیت</h6>
                <small>وضعیت نمایش و تولید کالا</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="checkbox-card">
                    <div class="form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="status" value="1"
                               <?php echo e(old('status', $product->status) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="status">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            فعال
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="checkbox-card">
                    <div class="form-check">
                        <input type="checkbox" name="in_production" class="form-check-input" id="in_production" value="1"
                               <?php echo e(old('in_production', $product->in_production) ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="in_production">
                            <i class="fas fa-cogs text-primary me-1"></i>
                            در تولید
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    
    
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-alias"><i class="fas fa-tags"></i></div>
            <div>
                <h6>نام‌های مستعار</h6>
                <small>نام‌های دیگر این محصول در فایل اکسل</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <input type="text" name="aliases"
                       class="form-control <?php $__errorArgs = ['aliases'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('aliases', $product->aliases->pluck('alias')->implode('، '))); ?>"
                       placeholder="مثال: بلسن بتا، بلسن بدون آرم، بلسن جوشا">
                <?php $__errorArgs = ['aliases'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback d-block"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    نام‌ها را با کاما (،) یا ویرگول (,) جدا کنید.
                </small>

                <?php if($product->aliases->count()): ?>
                    <div class="current-alias-box">
                        <strong>نام‌های مستعار فعلی:</strong>
                        <?php $__currentLoopData = $product->aliases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alias): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="badge bg-info me-1"><?php echo e($alias->alias); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="current-alias-box">
                        <span class="text-muted">هیچ نام مستعاری ثبت نشده است.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    
    
    <div class="action-bar">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                <i class="fas fa-asterisk text-danger me-1" style="font-size: 8px;"></i>
                فیلدهای اجباری
            </div>
            <div class="d-flex gap-2">
                <a href="<?php echo e(route('products.index')); ?>" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>
                    انصراف
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    به‌روزرسانی
                </button>
            </div>
        </div>
    </div>
</form>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/products/edit.blade.php ENDPATH**/ ?>