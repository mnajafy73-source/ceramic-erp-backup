<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات تولیدات تاریخ <?php echo e($date); ?></h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('productions.index')); ?>">لیست تولیدات</a></li>
            <li class="breadcrumb-item active">جزئیات <?php echo e($date); ?></li>
        </ol>
    </nav>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?php if($productions->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>اپراتور</th>
                            <th>محصول</th>
                            <th>عملیات</th>
                            <th>پرس</th>
                            <th>تعداد</th>
                            <th>زمان (ساعت)</th>
                            <th>توقف‌ها</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $productions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $production): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($index + 1); ?></td>
                                <td><?php echo e($production->operator->name ?? '-'); ?></td>
                                <td><?php echo e($production->product->name ?? '-'); ?></td>
                                <td><?php echo e($production->stage ?? '-'); ?></td>
                                <td><?php echo e($production->press->name ?? '-'); ?></td>
                                <td><?php echo e(number_format($production->quantity)); ?></td>
                                <td><?php echo e($production->time_hours ?? 0); ?></td>
                                <td>
                                    <?php $__currentLoopData = $production->stops; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stop): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="badge bg-warning"><?php echo e($stop->type); ?>: <?php echo e($stop->hours); ?>h</span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </td>
                                <td>
                                    <a href="<?php echo e(route('productions.show', $production)); ?>" class="btn btn-sm btn-info">مشاهده</a>
                                    <a href="<?php echo e(route('productions.edit', $production)); ?>" class="btn btn-sm btn-primary">ویرایش</a>
                                    <form action="<?php echo e(route('productions.destroy', $production)); ?>" method="POST" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این رکورد مطمئن هستید؟')">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <a href="<?php echo e(route('productions.index')); ?>" class="btn btn-secondary">بازگشت به لیست</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ تولیدی برای این تاریخ یافت نشد.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/productions/by-date.blade.php ENDPATH**/ ?>