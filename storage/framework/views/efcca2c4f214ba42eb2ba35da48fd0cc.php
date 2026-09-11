<?php $__env->startPush('styles'); ?>
<style>
    .hidden-row { background: #fff3cd !important; opacity: 0.75; }
    .hidden-row:hover { opacity: 1; }

    .btn-icon {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: 13px;
    }
    .column-action { width: 70px;  text-align: center; }
    .column-drag   { width: 40px;  text-align: center; }

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

    .sortable-ghost { background: #cfe2ff !important; opacity: 0.5; }
    .sortable-chosen { background: #e7f1ff !important; }

    .filter-bar {
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 10px; margin-bottom: 16px;
        padding: 12px 16px; background: #f8f9fa;
        border-radius: 10px; border: 1px solid #e9ecef;
    }
    .badge-count {
        background: #0d6efd; color: #fff;
        padding: 3px 10px; border-radius: 20px;
        font-size: 12px; font-weight: bold;
    }
    .badge-count-hidden {
        background: #ffc107; color: #333;
        padding: 3px 10px; border-radius: 20px;
        font-size: 12px; font-weight: bold;
    }
    .reorder-notice {
        background: #e7f1ff; color: #084298;
        padding: 6px 14px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
        border: 1px solid #b6d4fe;
    }
    .reorder-notice.disabled {
        background: #f1f3f5; color: #6c757d;
        border-color: #dee2e6;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
    var REORDER_URL = '<?php echo e(route("inventory.all-stocks.reorder")); ?>';

    // ============================================================
    //  Drag & Drop
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('allStocksTableBody');
        if (!tbody) return;

        if (tbody.querySelectorAll('tr[data-product-id]').length < 2) return;

        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                saveAllStocksOrder();
            }
        });
    });

    // ============================================================
    //  ذخیره ترتیب
    // ============================================================
    function saveAllStocksOrder() {
        var rows = document.querySelectorAll('#allStocksTableBody tr[data-product-id]');
        var order = [];

        rows.forEach(function(row) {
            var id = row.getAttribute('data-product-id');
            if (id) order.push(parseInt(id));
        });

        console.log('[AllStocks] Saving new order:', order);

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
            console.error('[AllStocks] Reorder error:', err);
            showToast('خطا در ذخیره ترتیب.', '#dc3545');
        });
    }

    // ============================================================
    //  Toast
    // ============================================================
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

<?php $__env->startSection('content'); ?>
<?php
    $showHidden = request('show_hidden') == '1';
    $rowCount = $stocks->count();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">گزارش جامع موجودی‌ها</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('inventory.index')); ?>">موجودی</a></li>
            <li class="breadcrumb-item active">گزارش جامع</li>
        </ol>
    </nav>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        
        <div class="filter-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge-count"><?php echo e($rowCount); ?> محصول</span>

                <?php if($showHidden): ?>
                    <span class="badge-count-hidden">
                        <i class="fas fa-eye me-1"></i> حالت نمایش همه
                    </span>
                <?php endif; ?>

                
                <?php if($rowCount < 2): ?>
                    <span class="reorder-notice disabled">
                        <i class="fas fa-info-circle me-1"></i>
                        حداقل ۲ محصول برای مرتب‌سازی لازمه
                    </span>
                <?php else: ?>
                    <span class="reorder-notice">
                        <i class="fas fa-arrows-alt me-1"></i>
                        برای تغییر ترتیب، ردیف‌ها را از آیکون <strong>⋮⋮</strong> بکشید
                    </span>
                <?php endif; ?>

                <span class="text-muted small">
                    — برای حذف از این گزارش، روی ❌ بزنید
                </span>
            </div>

            <div>
                <?php if($showHidden): ?>
                    <a href="<?php echo e(route('inventory.all-stocks')); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye-slash me-1"></i>
                        پنهان کردن محصولات مخفی‌شده
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(route('inventory.all-stocks', ['show_hidden' => 1])); ?>" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye me-1"></i>
                        نمایش محصولات مخفی‌شده
                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="column-drag">
                            <i class="fas fa-grip-vertical"></i>
                        </th>
                        <th>#</th>
                        <th>نام محصول</th>
                        <th class="text-center">اول دوره</th>
                        <th class="text-center">خام</th>
                        <th class="text-center">موم (۹۰۰°)</th>
                        <th class="text-center">شانه شده</th>
                        <th class="text-center">ضایعات</th>
                        <th class="text-center">۱۳۰۰°</th>
                        <th class="text-center">بسته بندی نشده</th>
                        <th class="text-center">انبار</th>
                        <th class="column-action">عملیات</th>
                    </tr>
                </thead>
                <tbody id="allStocksTableBody">
                    <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $isHidden = (bool) $item->product->hidden_from_all_stocks;
                        ?>
                        <tr data-product-id="<?php echo e($item->product->id); ?>" class="<?php echo e($isHidden ? 'hidden-row' : ''); ?>">

                            
                            <td class="column-drag">
                                <?php if($rowCount >= 2): ?>
                                    <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="opacity:0.3;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo e($loop->iteration); ?></td>

                            <td>
                                <?php echo e($item->product->name); ?>

                                <?php if($isHidden): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-eye-slash me-1"></i>مخفی
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center"><?php echo e(number_format($item->opening)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->raw)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->wax)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->shoulder)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->waste_mum)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->glaze1300)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->unpackaged ?? 0)); ?></td>
                            <td class="text-center"><?php echo e(number_format($item->warehouse)); ?></td>

                            <td class="column-action">
                                <?php if($isHidden): ?>
                                    <form action="<?php echo e(route('inventory.all-stocks.unhide', $item->product)); ?>"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('این محصول به گزارش برگردانده شود؟')">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-success btn-icon"
                                                title="برگرداندن به گزارش">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?php echo e(route('inventory.all-stocks.hide', $item->product)); ?>"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('این محصول از گزارش حذف شود؟\n(در دیتابیس می‌ماند و می‌توانید بعداً برگردانید)')">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger btn-icon"
                                                title="حذف از این گزارش">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="12" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                هیچ محصولی یافت نشد.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory/all-stocks.blade.php ENDPATH**/ ?>