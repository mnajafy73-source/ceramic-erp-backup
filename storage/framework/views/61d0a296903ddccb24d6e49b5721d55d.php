<?php $__env->startSection('title', 'تاریخچه تغییرات کالاها'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-0">تاریخچه تغییرات</h4>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('product_logs.index')); ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="جستجوی نام کالا..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            <?php if(request('search')): ?>
            <div class="col-md-2">
                <a href="<?php echo e(route('product_logs.index')); ?>" class="btn btn-outline-secondary w-100">پاک کردن</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>تاریخ</th>
                    <th>کاربر</th>
                    <th>کالا</th>
                    <th>عملیات</th>
                    <th>تغییرات</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e(\Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i')); ?></td>
                    <td><?php echo e($log->user->name ?? '—'); ?></td>
                    <td><?php echo e($log->product->name ?? '—'); ?></td>
                    <td>
                        <?php if($log->action == 'create'): ?> ایجاد
                        <?php elseif($log->action == 'update'): ?> ویرایش
                        <?php elseif($log->action == 'delete'): ?> حذف
                        <?php else: ?> <?php echo e($log->action); ?>

                        <?php endif; ?>
                    </td>
                    <td><?php echo e($log->persian_changes ?: '—'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">تاریخچه‌ای یافت نشد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3"><?php echo e($logs->links()); ?></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/product_logs/index.blade.php ENDPATH**/ ?>