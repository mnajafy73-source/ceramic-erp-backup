<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات پخت شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('shuttle.index')); ?>">کوره شاتل</a></li>
            <li class="breadcrumb-item active">جزئیات پخت شماره <?php echo e($firing->firing_number); ?></li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <!-- اطلاعات اصلی پخت -->
        <div class="row g-3 mb-4">
            <?php
                $kilnDisplay = 'نامشخص';
                if ($firing->kiln_type === 'packaging') {
                    $kilnDisplay = 'بسته‌بندی';
                } elseif (str_starts_with($firing->kiln_type, 'kiln_')) {
                    $kilnDisplay = 'کوره ' . substr($firing->kiln_type, 5);
                }

                $firingTypeDisplay = 'معمولی';
                if ($firing->kiln_type === 'kiln_2') {
                    $firingTypeDisplay = '۱۳۰۰';
                } elseif ($firing->kiln_type === 'kiln_3') {
                    if ($firing->firing_subtype === 'glaze') {
                        $firingTypeDisplay = 'لعابدار';
                    } elseif ($firing->firing_subtype === 'mum') {
                        $firingTypeDisplay = 'موم';
                    }
                }
            ?>
            <div class="col-md-3">
                <label class="fw-bold">شماره پخت:</label>
                <span class="badge bg-dark fs-6"><?php echo e($firing->firing_number); ?></span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">تاریخ:</label>
                <span><?php echo e($firing->date); ?></span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">کوره:</label>
                <span class="badge bg-primary"><?php echo e($kilnDisplay); ?></span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">نوع پخت:</label>
                <span class="badge bg-info"><?php echo e($firingTypeDisplay); ?></span>
            </div>
        </div>

        <hr>

        <!-- جدول آیتم‌های پخت -->
        <h6 class="fw-bold mb-3">محصولات این پخت (<?php echo e($firing->items->count()); ?> مورد)</h6>
        <?php if($firing->items->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>محصول</th>
                            <th>تعداد اصلی</th>
                            <th>بسته‌بندی</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $firing->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($index + 1); ?></td>
                                <td><?php echo e($item->product->name ?? 'نامشخص'); ?></td>
                                <td><?php echo e(number_format($item->output_quantity)); ?></td>
                                <td>
                                    <?php if($item->is_packaged): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    
                                    <a href="<?php echo e(route('shuttle.edit', [
                                        'year' => $firing->year,
                                        'month' => $firing->month,
                                        'day' => $firing->day,
                                        'kiln_type' => $firing->kiln_type,
                                        'firingNumber' => $firing->firing_number
                                    ])); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> ویرایش کل پخت
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="2" class="text-end">مجموع:</th>
                            <th><?php echo e(number_format($firing->total_quantity)); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">هیچ آیتمی برای این پخت یافت نشد.</div>
        <?php endif; ?>

        <div class="mt-3 d-flex gap-2">
            
            <a href="<?php echo e(route('shuttle.index')); ?>" class="btn btn-secondary">بازگشت به لیست</a>

            
            <a href="<?php echo e(route('shuttle.edit', [
                'year' => $firing->year,
                'month' => $firing->month,
                'day' => $firing->day,
                'kiln_type' => $firing->kiln_type,
                'firingNumber' => $firing->firing_number
            ])); ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> ویرایش پخت
            </a>

            
            <form action="<?php echo e(route('shuttle.destroy', [
                'year' => $firing->year,
                'month' => $firing->month,
                'day' => $firing->day,
                'kiln_type' => $firing->kiln_type,
                'firingNumber' => $firing->firing_number
            ])); ?>" method="POST" class="d-inline">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="btn btn-danger" onclick="return confirm('آیا از حذف این پخت مطمئن هستید؟')">
                    <i class="fas fa-trash"></i> حذف پخت
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/shuttle/show.blade.php ENDPATH**/ ?>