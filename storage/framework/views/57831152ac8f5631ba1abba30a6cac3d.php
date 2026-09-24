<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست تولیدات</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">تولیدات</li>
        </ol>
    </nav>
</div>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-bold text-muted small me-2">
                <i class="fas fa-filter me-1"></i> فیلتر منبع:
            </span>

            <a href="<?php echo e(route('productions.index', array_merge(request()->except('source', 'page'), ['source' => 'all']))); ?>"
               class="btn btn-sm <?php echo e($currentSource === 'all' ? 'btn-dark' : 'btn-outline-dark'); ?>"
               style="border-radius: 20px; padding: 4px 16px;">
                <i class="fas fa-list"></i> همه
            </a>

            <a href="<?php echo e(route('productions.index', array_merge(request()->except('source', 'page'), ['source' => 'manual']))); ?>"
               class="btn btn-sm <?php echo e($currentSource === 'manual' ? 'btn-success' : 'btn-outline-success'); ?>"
               style="border-radius: 20px; padding: 4px 16px;">
                <i class="fas fa-hand-paper"></i> دستی
            </a>

            <a href="<?php echo e(route('productions.index', array_merge(request()->except('source', 'page'), ['source' => 'imported']))); ?>"
               class="btn btn-sm <?php echo e($currentSource === 'imported' ? 'btn-primary' : 'btn-outline-primary'); ?>"
               style="border-radius: 20px; padding: 4px 16px;">
                <i class="fas fa-file-excel"></i> اکسل
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="<?php echo e(route('productions.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت تولید جدید
            </a>

            <div class="d-flex gap-2">
                <form action="<?php echo e(route('productions.clear-imported')); ?>" method="POST" class="d-inline"
                      onsubmit="return confirm('⚠️ مطمئن هستید؟ همه تولیدات ایمپورتی (اکسل) پاک می‌شوند.\n\nدستی‌ها حفظ می‌شوند.')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-outline-info">
                        <i class="fas fa-file-excel me-1"></i> حذف اکسل
                    </button>
                </form>
                <form action="<?php echo e(route('productions.clear-manual')); ?>" method="POST" class="d-inline"
                      onsubmit="return confirm('⚠️ مطمئن هستید؟ همه تولیدات دستی پاک می‌شوند.\n\nموجودی خام اصلاح می‌شود.\nاکسل‌ها حفظ می‌شوند.')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-outline-warning">
                        <i class="fas fa-hand-paper me-1"></i> حذف دستی
                    </button>
                </form>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-1"></i> <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-1"></i> <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
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
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>تاریخ</th>
                            <th>تعداد رکورد</th>
                            <th>مجموع تعداد</th>
                            <th>اپراتورها</th>
                            <th>محصولات</th>
                            <th>مراحل</th>
                            <th class="text-center">منبع</th>
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
                                    <span class="badge bg-light text-dark"><?php echo e($displayDate); ?></span>
                                </td>
                                <td><?php echo e(number_format($group->total_rows)); ?></td>
                                <td class="fw-bold"><?php echo e(number_format($group->total_quantity)); ?></td>
                                <td><?php echo e($group->operators_text); ?></td>
                                <td><?php echo e($group->products_text); ?></td>
                                <td><?php echo e($group->stages_text); ?></td>

                                <td class="text-center">
                                    <?php if($group->source === 'mixed'): ?>
                                        <span class="badge bg-secondary">ترکیبی</span>
                                    <?php elseif($group->source === 'imported'): ?>
                                        <span class="badge bg-primary">اکسل</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">دستی</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="<?php echo e(route('productions.by-date', ['date' => $group->date])); ?>" class="btn btn-sm btn-info">
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
            <div class="alert alert-info">
                <?php if($currentSource === 'manual'): ?>
                    هیچ تولید دستی ثبت نشده است.
                <?php elseif($currentSource === 'imported'): ?>
                    هیچ تولید ایمپورتی (اکسل) وجود ندارد.
                <?php else: ?>
                    هیچ تولیدی ثبت نشده است.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/productions/index.blade.php ENDPATH**/ ?>