

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">گزارش جامع موجودی‌ها</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('inventory.index')); ?>">موجودی</a></li>
            <li class="breadcrumb-item active">گزارش جامع</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>نام محصول</th>
                        <th class="text-center">موجودی اول دوره</th>
                        <th class="text-center">موجودی خام</th>
                        <th class="text-center">موجودی موم (۹۰۰°)</th>
                        <th class="text-center">موجودی ۱۳۰۰°</th>
                        <th class="text-center">موجودی انبار</th>
                        <th class="text-center">موجودی شانه شده</th>
                        <th class="text-center">ضایعات موم</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($loop->iteration); ?></td>
                            <td><?php echo e($item->product->name); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->opening)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->raw)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->wax)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->glaze1300)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->warehouse)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->shoulder)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->waste_mum)); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="text-center">هیچ محصولی یافت نشد.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory/all-stocks.blade.php ENDPATH**/ ?>