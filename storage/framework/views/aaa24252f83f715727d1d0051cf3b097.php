

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">قیمت تمام شده</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">قیمت تمام شده</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">آخرین قیمت هر ماده اولیه</h5>
            </div>
            <div class="card-body">
                <?php if(count($lastRawMaterialPurchases) > 0): ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ماده</th>
                                <th>آخرین قیمت هر گرم (ریال)</th>
                                <th>تاریخ خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $lastRawMaterialPurchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($item->raw_material->name); ?></td>
                                <td><?php echo e(number_format($item->price_per_gram, 2)); ?></td>
                                <td><?php echo e(jdate($item->purchase_date)->format('Y/m/d')); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">هیچ خریدی ثبت نشده است.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">آخرین قیمت هر کارتن/لایه</h5>
            </div>
            <div class="card-body">
                <?php if(count($lastPackagingPurchases) > 0): ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>نام</th>
                                <th>آخرین قیمت هر عدد (ریال)</th>
                                <th>تاریخ خرید</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $lastPackagingPurchases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($item->packaging->name); ?></td>
                                <td><?php echo e(number_format($item->price_per_unit, 2)); ?></td>
                                <td><?php echo e(jdate($item->purchase_date)->format('Y/m/d')); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">هیچ خریدی ثبت نشده است.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/cost-price/index.blade.php ENDPATH**/ ?>