<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .column-drag { width: 40px; text-align: center; }

    .drag-handle {
        cursor: grab;
        color: #adb5bd;
        font-size: 18px;
        transition: color 0.2s;
        user-select: none;
        padding: 4px 8px;
    }
    .drag-handle:hover { color: #0d6efd; }
    .drag-handle:active { cursor: grabbing; }

    .drag-handle.disabled {
        cursor: not-allowed;
        opacity: 0.25;
    }

    .sortable-ghost { background: #cfe2ff !important; opacity: 0.5; }
    .sortable-chosen { background: #e7f1ff !important; }

    .reorder-notice {
        background: #e7f1ff;
        color: #084298;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #b6d4fe;
        display: inline-block;
        margin-bottom: 12px;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">داشبورد مدیریت</h3>

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

                    <?php if(session('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo e(session('success')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(session('info')): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo e(session('info')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($allProducts) && $allProducts->count() > 0): ?>

                        <?php if($allProducts->count() >= 2): ?>
                            <div class="reorder-notice">
                                <i class="fas fa-arrows-alt me-1"></i>
                                برای تغییر ترتیب، ردیف‌ها را از آیکون <strong>⋮⋮</strong> بکشید
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="column-drag">
                                            <i class="fas fa-grip-vertical"></i>
                                        </th>
                                        <th>نام محصول</th>
                                        <th class="text-center">موجودی (عدد)</th>
                                        <th class="text-center">زمان پخت تونلی (ساعت)</th>
                                        <th class="text-center">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboardTableBody">
                                    <?php $__currentLoopData = $allProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr data-product-id="<?php echo e($product->id); ?>">

                                        <td class="column-drag">
                                            <?php if($allProducts->count() >= 2): ?>
                                                <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="drag-handle disabled">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td><?php echo e($product->name); ?></td>

                                        <td class="text-center"><?php echo e(number_format($product->stock ?? 0)); ?></td>

                                        <td class="text-center">
                                            <?php if($product->tonneli_time !== null): ?>
                                                <?php echo e(number_format($product->tonneli_time, 1)); ?> ساعت
                                            <?php else: ?>
                                                <span class="text-muted">نامشخص</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
    var REORDER_URL = '<?php echo e(route("dashboard.reorder")); ?>';

    // ============================================================
    //  Select2
    // ============================================================
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'محصولی یافت نشد'; }
            }
        });
    });

    // ============================================================
    //  Drag & Drop
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('dashboardTableBody');
        if (!tbody) return;

        if (tbody.querySelectorAll('tr[data-product-id]').length < 2) return;

        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                saveOrder();
            }
        });
    });

    function saveOrder() {
        var rows = document.querySelectorAll('#dashboardTableBody tr[data-product-id]');
        var order = [];

        rows.forEach(function(row) {
            var id = row.getAttribute('data-product-id');
            if (id) order.push(parseInt(id));
        });

        fetch(REORDER_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ order: order }),
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('✅ ترتیب با موفقیت ذخیره شد.', '#198754');
            } else {
                showToast('خطا: ' + (data.error || 'نامشخص'), '#dc3545');
            }
        })
        .catch(function(err) {
            console.error('[Dashboard] Reorder error:', err);
            showToast('خطا در ذخیره ترتیب.', '#dc3545');
        });
    }

    function showToast(message, bgColor) {
        bgColor = bgColor || '#198754';
        var toast = document.createElement('div');
        toast.style.cssText = 'position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; ' +
                              'background:' + bgColor + '; color:#fff; padding:12px 24px; border-radius:8px; ' +
                              'font-weight:bold; box-shadow:0 4px 12px rgba(0,0,0,0.2); font-size:14px;';
        toast.innerHTML = message;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 500);
        }, 2200);
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/dashboard.blade.php ENDPATH**/ ?>