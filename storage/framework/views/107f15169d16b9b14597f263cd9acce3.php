<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">مدیریت کالاها</h4>
    <a href="<?php echo e(route('products.create')); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> کالای جدید</a>
</div>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?php echo e(route('products.index')); ?>" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">جستجو</label>
                <input type="text" name="search" class="form-control" placeholder="کد یا نام کالا..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo e(route('products.index')); ?>" class="btn btn-secondary w-100">حذف فیلتر</a>
            </div>
            <div class="col-md-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        مرتب‌سازی
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'asc']))); ?>">کد (صعودی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'desc']))); ?>">کد (نزولی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'asc']))); ?>">نام (صعودی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'desc']))); ?>">نام (نزولی)</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>کد</th>
                        <th>نام</th>
                        <th>واحد</th>
                        <th>نوع کوره</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo e($product->code); ?></span></td>
                        <td><?php echo e($product->name); ?></td>
                        <td><?php echo e($product->unit->name ?? '—'); ?></td>
                        <td>
                            <?php
                                $kilnLabels = ['tonneli' => 'تونلی', 'shuttle' => 'شاتل', 'both' => 'هر دو'];
                            ?>
                            <?php echo e($kilnLabels[$product->kiln_type] ?? $product->kiln_type); ?>

                        </td>
                        <td>
                            <span class="badge <?php echo e($product->status ? 'bg-success' : 'bg-danger'); ?>">
                                <?php echo e($product->status ? 'فعال' : 'غیرفعال'); ?>

                            </span>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="<?php echo e(route('products.show', $product)); ?>" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                            <a href="<?php echo e(route('products.edit', $product)); ?>" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                            <form action="<?php echo e(route('products.destroy', $product)); ?>" method="POST" onsubmit="return confirm('مطمئن هستید این کالا حذف شود؟')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center">هیچ کالایی یافت نشد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($products->links()); ?></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/products/index.blade.php ENDPATH**/ ?>