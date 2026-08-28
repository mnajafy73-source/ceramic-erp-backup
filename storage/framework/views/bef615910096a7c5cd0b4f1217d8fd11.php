

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">واردات از اکسل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">واردات</li>
        </ol>
    </nav>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
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

<div class="row g-4">
    <!-- واردات تولید -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">📥 تولید</h5>
                <p class="text-muted small">برگه: تولید</p>
                <form action="<?php echo e(route('import.productions')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" class="form-control mb-2" accept=".xlsx,.xls" required>
                    <button type="submit" class="btn btn-primary w-100">واردات</button>
                </form>
            </div>
        </div>
    </div>

    <!-- واردات کوره تونلی -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">🔥 تونلی</h5>
                <p class="text-muted small">برگه: کوره تونلی</p>
                <form action="<?php echo e(route('import.tonneli')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" class="form-control mb-2" accept=".xlsx,.xls" required>
                    <button type="submit" class="btn btn-primary w-100">واردات</button>
                </form>
            </div>
        </div>
    </div>

    <!-- واردات کوره شاتل -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">🔄 شاتل</h5>
                <p class="text-muted small">برگه: کوره شاتل</p>
                <form action="<?php echo e(route('import.shuttle')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" class="form-control mb-2" accept=".xlsx,.xls" required>
                    <button type="submit" class="btn btn-primary w-100">واردات</button>
                </form>
            </div>
        </div>
    </div>

    <!-- واردات فروش غیررسمی (جدید) -->
    <div class="col-md-4 mt-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title">🧾 فروش غیررسمی</h5>
                <p class="text-muted small">برگه: غیر رسمی</p>
                <form action="<?php echo e(route('import.informal-sales')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" class="form-control mb-2" accept=".xlsx,.xls" required>
                    <button type="submit" class="btn btn-primary w-100">واردات</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-1">📂 واردات خودکار از مسیر</h5>
                        <p class="text-muted small mb-0">
                            مسیر تنظیم‌شده در فایل .env:
                            <code><?php echo e(env('EXCEL_FILE_PATH', 'تنظیم نشده')); ?></code>
                        </p>
                    </div>
                    <a href="<?php echo e(route('import.from-path')); ?>" class="btn btn-success">
                        <i class="fas fa-sync-alt me-1"></i> واردات خودکار
                    </a>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    برای تنظیم مسیر، کلید <code>EXCEL_FILE_PATH</code> را در فایل .env قرار دهید.
                    مثال: <code>EXCEL_FILE_PATH=E:\data\production.xlsx</code>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2 flex-wrap">
    <a href="<?php echo e(route('productions.index')); ?>" class="btn btn-secondary">مشاهده تولیدات</a>
    <a href="<?php echo e(route('tonneli.index')); ?>" class="btn btn-secondary">مشاهده تونلی</a>
    <a href="<?php echo e(route('shuttle.index')); ?>" class="btn btn-secondary">مشاهده شاتل</a>
    <a href="<?php echo e(route('informal-sales.index')); ?>" class="btn btn-secondary">مشاهده فروش غیررسمی</a>
    <a href="<?php echo e(route('inventory.raw')); ?>" class="btn btn-secondary">موجودی خام</a>
    <a href="<?php echo e(route('inventory.warehouse')); ?>" class="btn btn-secondary">موجودی انبار</a>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/import/index.blade.php ENDPATH**/ ?>