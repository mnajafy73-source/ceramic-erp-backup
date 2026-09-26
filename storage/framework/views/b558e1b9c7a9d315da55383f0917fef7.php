<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست پخت‌های کوره شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">داشبورد</a></li>
            <li class="breadcrumb-item active">کوره شاتل</li>
        </ol>
    </nav>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <a href="<?php echo e(route('shuttle.create')); ?>" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> ثبت پخت جدید
            </a>

            <div class="d-flex gap-2">
                <?php if(request('source') === 'imported'): ?>
                    <form action="<?php echo e(route('shuttle.clear-imported')); ?>" method="POST" onsubmit="return confirm('همه رکوردهای ایمپورتی پاک بشن؟')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> پاک کردن ایمپورتی‌ها
                        </button>
                    </form>
                <?php endif; ?>
                <?php if(request('source') === 'manual'): ?>
                    <form action="<?php echo e(route('shuttle.clear-manual')); ?>" method="POST" onsubmit="return confirm('همه رکوردهای دستی پاک بشن؟ (موجودی برگردانده می‌شود)')">
                        <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash me-1"></i> پاک کردن دستی‌ها
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
            <span class="fw-bold small text-muted me-2">
                <i class="fas fa-filter me-1"></i> منبع:
            </span>
            <?php
                $currentSource = request('source', 'all');
                $sourceButtons = [
                    'all'      => ['label' => 'همه',           'color' => 'dark'],
                    'manual'   => ['label' => 'دستی',          'color' => 'primary'],
                    'imported' => ['label' => 'ایمپورت اکسل',  'color' => 'info'],
                ];
            ?>
            <?php $__currentLoopData = $sourceButtons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cfg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('shuttle.index', array_merge(request()->except('source', 'page'), ['source' => $key]))); ?>"
                   class="btn btn-sm <?php echo e($currentSource === $key ? 'btn-' . $cfg['color'] : 'btn-outline-' . $cfg['color']); ?>">
                    <?php echo e($cfg['label']); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
        <?php if($allKilnCounts->count()): ?>
            <div class="row g-2 mb-3">
                <div class="col-auto">
                    <a href="<?php echo e(route('shuttle.index', array_merge(request()->except('kiln'), ['kiln' => 'all']))); ?>"
                       class="badge <?php echo e(is_null($filterKiln) || $filterKiln === 'all' ? 'bg-dark' : 'bg-secondary'); ?> p-2 fs-6 text-decoration-none">
                        همه (<?php echo e($allKilnCounts->sum()); ?>)
                    </a>
                </div>
                <?php $__currentLoopData = $allKilnCounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kilnType => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $kilnDisplay = 'نامشخص';
                        if ($kilnType === 'packaging') {
                            $kilnDisplay = 'بسته‌بندی';
                        } elseif (str_starts_with($kilnType, 'kiln_')) {
                            $kilnDisplay = 'کوره ' . substr($kilnType, 5);
                        }
                        $isActive = ($filterKiln == $kilnType);
                    ?>
                    <div class="col-auto">
                        <a href="<?php echo e(route('shuttle.index', array_merge(request()->except('kiln'), ['kiln' => $kilnType]))); ?>"
                           class="badge <?php echo e($isActive ? 'bg-primary' : 'bg-secondary'); ?> p-2 fs-6 text-decoration-none">
                            <?php echo e($kilnDisplay); ?>: <?php echo e($count); ?> پخت
                        </a>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php if($paginated->count()): ?>
            <div class="mb-2 text-muted small">
                نمایش <?php echo e($paginated->firstItem()); ?> تا <?php echo e($paginated->lastItem()); ?> از <?php echo e($paginated->total()); ?> پخت
            </div>
        <?php endif; ?>

        <?php if($paginated->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>شماره پخت</th>
                            <th>تاریخ</th>
                            <th>کوره</th>
                            <th>نوع پخت</th>
                            <th>تعداد محصولات</th>
                            <th>مجموع تعداد</th>
                            <th>بسته‌بندی</th>
                            <th>منبع</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $paginated; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $firing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
                            <tr>
                                <td><?php echo e($paginated->firstItem() + $index); ?></td>
                                <td><?php echo e($firing->firing_number); ?></td>
                                <td><?php echo e($firing->date); ?></td>
                                <td><span class="badge bg-primary"><?php echo e($kilnDisplay); ?></span></td>
                                <td><span class="badge bg-info"><?php echo e($firingTypeDisplay); ?></span></td>
                                <td><?php echo e($firing->products_count); ?></td>
                                <td><?php echo e(number_format($firing->total_quantity)); ?></td>
                                <td>
                                    <?php if($firing->is_packaged): ?>
                                        <span class="badge bg-success">بله</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">خیر</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($firing->is_imported): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-file-excel me-1"></i> اکسل
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-hand-paper me-1"></i> دستی
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    
                                    <a href="<?php echo e(route('shuttle.show', [
                                        'year' => $firing->year,
                                        'month' => $firing->month,
                                        'day' => $firing->day,
                                        'kiln_type' => $firing->kiln_type,
                                        'firingNumber' => $firing->firing_number
                                    ])); ?>" class="btn btn-sm btn-info" title="مشاهده و ویرایش">
                                        <i class="fas fa-eye"></i> مشاهده
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
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف کل این پخت مطمئن هستید؟')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <?php echo e($paginated->links()); ?>

            </div>
        <?php else: ?>
            <div class="alert alert-info">
                هیچ پخت شاتلی ثبت نشده است.
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/shuttle/index.blade.php ENDPATH**/ ?>