

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">📋 لیست مواد سازی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">مواد سازی</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="<?php echo e(route('import.index')); ?>" class="btn btn-primary">
                <i class="fas fa-file-import me-1"></i> واردات از اکسل
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        <?php if($records->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>سال</th>
                            <th>ماه</th>
                            <th>روز</th>
                            <th>نام</th>
                            <th>فرمول</th>
                            <th>تعداد بالمیل</th>
                            <th>وزن بالمیل (کیلوگرم)</th>
                            
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($records->firstItem() + $index); ?></td>
                                <td><?php echo e($record->year); ?></td>
                                <td><?php echo e($record->month); ?></td>
                                <td><?php echo e($record->day); ?></td>
                                <td><?php echo e($record->name ?? '-'); ?></td>
                                <td><?php echo e($record->material); ?></td>
                                <td><?php echo e(number_format($record->quantity)); ?></td>
                                
                                <td><?php echo e(number_format($record->mill_weight / 1000, 2)); ?></td>
                                <td>
                                    <form action="<?php echo e(route('material-making.destroy', $record->id)); ?>" method="POST" class="d-inline"
                                          onsubmit="return confirm('آیا از حذف این رکورد مطمئن هستید؟')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($records->links()); ?></div>
        <?php else: ?>
            <div class="alert alert-info">هیچ رکوردی ثبت نشده است.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/material-making/index.blade.php ENDPATH**/ ?>