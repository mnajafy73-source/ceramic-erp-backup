

<?php $__env->startPush('styles'); ?>
<style>
    .inventory-section {
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        background: #f8f9fa;
    }
    .inventory-section .section-title {
        font-weight: bold;
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .inventory-table td, .inventory-table th {
        vertical-align: middle;
    }
    .inventory-table input[type="number"] {
        width: 120px;
        text-align: left;
        direction: ltr;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">🛠️ به‌روزرسانی دستی موجودی‌ها</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">داشبورد</a></li>
            <li class="breadcrumb-item"><a href="<?php echo e(route('settings.manual-inventory')); ?>">تنظیمات</a></li>
            <li class="breadcrumb-item active">به‌روزرسانی موجودی</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            در این صفحه می‌توانید همه‌ی موجودی‌های سیستم را به‌صورت دستی ویرایش کنید. 
            مقادیر خالی یا صفر، موجودی را به‌روز نمی‌کنند.
            <strong>توجه:</strong> پس از ذخیره، سیستم از همین موجودی‌ها برای محاسبات بعدی استفاده می‌کند.
        </div>

        <form action="<?php echo e(route('settings.manual-inventory.update')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            
            <?php if($products->count()): ?>
            <div class="inventory-section">
                <h5 class="section-title">📦 موجودی اول دوره</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی اول دوره</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="opening[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($openingInventories->has($product->id) ? $openingInventories[$product->id]->quantity : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="inventory-section">
                <h5 class="section-title">📊 موجودی خام (محاسبه‌شده از سیستم)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی خام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td class="fw-bold"><?php echo e(number_format($rawStocks[$product->id] ?? 0)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">موجودی خام به‌صورت خودکار محاسبه می‌شود و قابل ویرایش دستی نیست.</small>
            </div>

            
            <div class="inventory-section">
                <h5 class="section-title">🔥 موجودی موم (۹۰۰ درجه)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی موم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="wax[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($waxInventories->has($product->id) ? $waxInventories[$product->id]->stock : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="inventory-section">
                <h5 class="section-title">🔥 موجودی ۱۳۰۰ درجه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی ۱۳۰۰ درجه</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="glaze1300[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($glaze1300Inventories->has($product->id) ? $glaze1300Inventories[$product->id]->stock : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="inventory-section">
                <h5 class="section-title">🏭 موجودی انبار</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی انبار</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="warehouse[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($warehouseInventories->has($product->id) ? $warehouseInventories[$product->id]->stock : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="inventory-section">
                <h5 class="section-title">🧴 موجودی شانه شده</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی شانه شده</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="shoulder[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($shoulderInventories->has($product->id) ? $shoulderInventories[$product->id]->stock : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div class="inventory-section">
                <h5 class="section-title">🗑️ ضایعات موم</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>ضایعات موم</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($product->name); ?></td>
                                <td>
                                    <input type="number" name="waste_mum[<?php echo e($product->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($wasteMumInventories->has($product->id) ? $wasteMumInventories[$product->id]->stock : 0); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <?php if($rawMaterials->count()): ?>
            <div class="inventory-section">
                <h5 class="section-title">🧪 موجودی مواد اولیه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام ماده</th>
                                <th>موجودی (گرم)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $rawMaterials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($material->name); ?></td>
                                <td>
                                    <input type="number" name="raw_material[<?php echo e($material->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($material->stock); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            
            <?php if($packagings->count()): ?>
            <div class="inventory-section">
                <h5 class="section-title">📦 موجودی کارتن و لایه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نوع</th>
                                <th>نام</th>
                                <th>موجودی (عدد)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($packaging->type == 'carton' ? 'کارتن' : 'لایه'); ?></td>
                                <td><?php echo e($packaging->name); ?></td>
                                <td>
                                    <input type="number" name="packaging[<?php echo e($packaging->id); ?>]" 
                                           class="form-control form-control-sm" 
                                           value="<?php echo e($packaging->stock); ?>"
                                           min="0" step="1">
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            
            <div class="d-flex justify-content-between align-items-center mt-4 gap-3 flex-wrap">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i> ذخیره همه موجودی‌ها
                </button>
                <div>
                    <button type="button" class="btn btn-danger btn-lg" onclick="confirmReset()">
                        <i class="fas fa-trash-alt me-2"></i> صفر کردن همه موجودی‌ها
                    </button>
                    <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i> انصراف
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>


<form id="resetForm" action="<?php echo e(route('settings.manual-inventory.reset')); ?>" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <?php echo method_field('DELETE'); ?>
</form>

<script>
    function confirmReset() {
        if (confirm('⚠️ هشدار! آیا از صفر کردن همه موجودی‌ها مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
            if (confirm('تأیید نهایی: آیا مطمئن هستید که می‌خواهید همه موجودی‌ها را صفر کنید؟')) {
                document.getElementById('resetForm').submit();
            }
        }
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/settings/manual-inventory.blade.php ENDPATH**/ ?>