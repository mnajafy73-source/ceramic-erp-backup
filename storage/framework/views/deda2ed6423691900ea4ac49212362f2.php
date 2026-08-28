<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست فروش‌های غیررسمی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">داشبورد</a></li>
            <li class="breadcrumb-item active">فروش غیررسمی</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <a href="<?php echo e(route('informal-sales.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> ثبت فروش جدید
            </a>
        </div>

        <?php if($sales->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>شماره فاکتور</th>
                            <th>تاریخ</th>
                            <th>مشتری</th>
                            <th>قیمت کل</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $sales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($sale->display_number); ?></td>
                                <td><?php echo e($sale->jalali_date); ?></td>
                                <td><?php echo e($sale->customer_name ?? 'نامشخص'); ?></td>
                                <td><?php echo e(number_format($sale->total_price)); ?> ریال</td>
                                <td>
                                    <?php
                                        $statusLabels = ['unpaid' => 'پرداخت نشده', 'paid' => 'پرداخت شده', 'canceled' => 'لغو شده'];
                                        $statusClass = ['unpaid' => 'bg-warning', 'paid' => 'bg-success', 'canceled' => 'bg-danger'];
                                        $status = $sale->status ?? 'unpaid';
                                    ?>
                                    <span class="badge <?php echo e($statusClass[$status] ?? 'bg-secondary'); ?>">
                                        <?php echo e($statusLabels[$status] ?? 'نامشخص'); ?>

                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('informal-sales.show', $sale)); ?>" class="btn btn-sm btn-info">مشاهده</a>
                                    <a href="<?php echo e(route('informal-sales.edit', $sale)); ?>" class="btn btn-sm btn-primary">ویرایش</a>
                                    <form action="<?php echo e(route('informal-sales.destroy', $sale)); ?>" method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('حذف؟')">حذف</button>
                                    </form>
                                    <?php if($sale->status !== 'paid'): ?>
                                        <form action="<?php echo e(route('informal-sales.paid', $sale)); ?>" method="POST" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-success">پرداخت شد</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($sales->links()); ?></div>
        <?php else: ?>
            <div class="alert alert-info">هیچ فروش غیررسمی ثبت نشده است.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/informal-sales/index.blade.php ENDPATH**/ ?>