<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">داشبورد مدیریت</h3>

                    <!-- فرم افزودن محصول -->
                    <form action="<?php echo e(route('dashboard.add-product')); ?>" method="POST" class="d-flex align-items-center gap-2 flex-wrap">
                        <?php echo csrf_field(); ?>
                        <div class="form-group mb-0" style="min-width: 280px;">
                            <select name="product_id" class="form-control product-search-select" style="width: 100%;" required>
                                <option value="">جستجو و انتخاب محصول...</option>
                                <?php $__currentLoopData = $allProductsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?> (<?php echo e($product->code); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if(isset($allProducts) && $allProducts->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>نام محصول</th>
                                        <th>موجودی (عدد)</th>
                                        <th>زمان پخت تونلی (ساعت)</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $allProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($product->name); ?></td>
                                        <td><?php echo e(number_format($product->stock ?? 0)); ?></td>
                                        <td>
                                            <?php if($product->tonneli_time !== null): ?>
                                                <?php echo e(number_format($product->tonneli_time, 1)); ?> ساعت
                                            <?php else: ?>
                                                <span class="text-muted">نامشخص</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="<?php echo e(route('dashboard.remove-product')); ?>" method="POST" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این محصول از داشبورد مطمئن هستید؟')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            هیچ محصولی به داشبورد اضافه نشده است. از قسمت بالا محصول مورد نظر را جستجو و اضافه کنید.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() {
                    return 'در حال جستجو...';
                },
                noResults: function() {
                    return 'محصولی یافت نشد';
                }
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/dashboard.blade.php ENDPATH**/ ?>