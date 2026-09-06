

<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.material-search-select').select2({
            placeholder: 'جستجو...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'موردی یافت نشد'; }
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('inventory.index')); ?>">موجودی</a></li>
            <li class="breadcrumb-item active">مواد اولیه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="<?php echo e(route('inventory.raw-materials')); ?>" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select material-search-select" style="width: 100%;">
                        <option value="">همه مواد...</option>
                        <?php $__currentLoopData = \App\Models\RawMaterial::orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($material->id); ?>" <?php echo e(request('search') == $material->id ? 'selected' : ''); ?>>
                                <?php echo e($material->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    <?php if(request('search')): ?>
                        <a href="<?php echo e(route('inventory.raw-materials')); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>نام ماده</th>
                        <th>موجودی (گرم)</th>
                        <th>موجودی (تن)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $stockInGram = (int) $material->stock;
                            $stockInTon = $stockInGram / 1000000;
                            
                            $formattedGram = number_format($stockInGram, 0);
                            $formattedTon = rtrim(rtrim(number_format($stockInTon, 3, '.', ''), '0'), '.');
                            if ($formattedTon === '') {
                                $formattedTon = '0';
                            }
                        ?>
                        <tr>
                            <td><?php echo e($material->name); ?></td>
                            <td><?php echo e($formattedGram); ?></td>
                            <td><?php echo e($formattedTon); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="3" class="text-center">
                            <?php if(request('search')): ?>
                                ماده‌ای با این شناسه یافت نشد.
                            <?php else: ?>
                                هیچ ماده اولیه‌ای ثبت نشده است.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory/raw-materials.blade.php ENDPATH**/ ?>