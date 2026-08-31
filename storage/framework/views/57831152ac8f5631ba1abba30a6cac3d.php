<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست تولیدات</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">تولیدات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <a href="<?php echo e(route('productions.create')); ?>" class="btn btn-primary">ثبت تولید جدید</a>
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

        <?php if($productions->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>تاریخ</th>
                            <th>تعداد رکورد</th>
                            <th>مجموع تعداد</th>
                            <th>اپراتورها</th>
                            <th>محصولات</th>
                            <th>عملیات‌ها</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $productions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $dateParts = explode('/', $group->date);
                                $year = $dateParts[0] ?? '';
                                $month = $dateParts[1] ?? '';
                                $day = $dateParts[2] ?? '';
                            ?>
                            <tr>
                                <td><?php echo e($productions->firstItem() + $index); ?></td>
                                <td>
                                    <?php
                                        try {
                                            \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $group->date);
                                            $displayDate = $group->date;
                                        } catch (\Exception $e) {
                                            $displayDate = 'نامعتبر';
                                        }
                                    ?>
                                    <?php echo e($displayDate); ?>

                                </td>
                                <td><?php echo e(number_format($group->total_rows)); ?></td>
                                <td><?php echo e(number_format($group->total_quantity)); ?></td>
                                <td><?php echo e($group->operators_text); ?></td>
                                <td><?php echo e($group->products_text); ?></td>
                                <td><?php echo e($group->stages_text); ?></td>
                                <td>
                                    <a href="<?php echo e(route('productions.show-by-date', ['date' => $group->date])); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> جزئیات
                                    </a>

                                    
                                    <?php if($year && $month && $day): ?>
                                        <form action="<?php echo e(route('productions.destroy-group', ['year' => $year, 'month' => $month, 'day' => $day])); ?>" method="POST" class="d-inline" 
                                              onsubmit="return confirm('آیا از حذف تمام تولیدات تاریخ <?php echo e($group->date); ?> مطمئن هستید؟');">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> حذف گروه
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <?php echo e($productions->links()); ?>

            </div>
        <?php else: ?>
            <div class="alert alert-info">هیچ تولیدی ثبت نشده است.</div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/productions/index.blade.php ENDPATH**/ ?>