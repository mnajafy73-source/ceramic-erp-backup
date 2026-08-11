<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست پخت‌های شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">پخت شاتل</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="<?php echo e(route('shuttle.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت پخت جدید
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
        <?php endif; ?>

        
        <div class="row mb-4">
            <?php $__currentLoopData = $summaryData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-light">
                        <div class="card-body text-center py-2">
                            <h6 class="card-title mb-0"><?php echo e($data['label']); ?></h6>
                            <span class="badge bg-primary"><?php echo e($data['count']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>تاریخ</th>
                        <th>نوع کوره</th>
                        <th>شماره پخت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($batches->firstItem() + $index); ?></td>
                        <td><?php echo e(\Morilog\Jalali\Jalalian::fromCarbon($batch->date)->format('Y/m/d')); ?></td>
                        <td><?php echo e($kilnLabels[$batch->kiln_type] ?? $batch->kiln_type); ?></td>
                        <td><?php echo e($batch->firing_number); ?></td>
                        <td>
                            <a href="<?php echo e(route('shuttle.show', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type])); ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo e(route('shuttle.edit', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type])); ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="<?php echo e(route('shuttle.destroy-batch')); ?>" method="POST" class="d-inline" 
                                  onsubmit="return confirm('آیا از حذف این پخت مطمئن هستید؟')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <input type="hidden" name="firing_number" value="<?php echo e($batch->firing_number); ?>">
                                <input type="hidden" name="date" value="<?php echo e($batch->date->format('Y-m-d')); ?>">
                                <input type="hidden" name="kiln_type" value="<?php echo e($batch->kiln_type); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="text-center">هیچ پخت شاتلی ثبت نشده است.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php echo e($batches->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/shuttle/index.blade.php ENDPATH**/ ?>