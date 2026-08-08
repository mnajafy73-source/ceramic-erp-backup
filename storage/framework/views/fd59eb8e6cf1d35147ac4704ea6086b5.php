

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات خرید کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('packaging-purchases.index')); ?>">خرید کارتن و لایه</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>تاریخ خرید</th><td><?php echo e(jdate($packagingPurchase->purchase_date)->format('Y/m/d')); ?></td></tr>
                    <tr><th>تأمین‌کننده</th><td><?php echo e($packagingPurchase->supplier ?? '-'); ?></td></tr>
                    <tr><th>هزینه حمل‌ونقل</th><td><?php echo e(number_format($packagingPurchase->total_transport_cost)); ?> ریال</td></tr>
                </table>
            </div>
        </div>

        <h5 class="fw-bold">اقلام خریداری‌شده</h5>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>تعداد</th>
                    <th>قیمت کل (ریال)</th>
                    <th>قیمت هر عدد (ریال)</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $packagingPurchase->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($loop->iteration); ?></td>
                    <td><?php echo e($item->packaging->name); ?></td>
                    <td><?php echo e($item->packaging->type == 'carton' ? 'کارتن' : 'لایه'); ?></td>
                    <td><?php echo e(number_format($item->quantity)); ?></td>
                    <td><?php echo e(number_format($item->total_price)); ?></td>
                    <td><?php echo e(number_format($item->price_per_unit, 2)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="mt-3">
            <a href="<?php echo e(route('packaging-purchases.index')); ?>" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/packaging-purchases/show.blade.php ENDPATH**/ ?>