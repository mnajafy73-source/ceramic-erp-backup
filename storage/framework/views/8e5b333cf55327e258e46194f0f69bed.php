<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .column-drag { width: 40px; text-align: center; }
    .drag-handle { cursor: grab; color: #adb5bd; font-size: 18px; user-select: none; padding: 4px 8px; }
    .drag-handle:hover { color: #0d6efd; }
    .drag-handle.disabled { cursor: not-allowed; opacity: 0.25; }
    .sortable-ghost { background: #cfe2ff !important; opacity: 0.5; }
    .sortable-chosen { background: #e7f1ff !important; }

    .reorder-notice {
        background: #e7f1ff; color: #084298;
        padding: 6px 14px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
        border: 1px solid #b6d4fe;
        display: inline-block; margin-bottom: 12px;
    }

    .customer-cell { padding-right: 24px; }
    .customer-cell::before {
        content: '└'; color: #adb5bd;
        margin-left: 6px; font-size: 12px;
    }
    .product-name-cell { font-weight: bold; color: #1e3a5f; vertical-align: middle !important; }
    .customer-first-row { border-top: 2px solid #dee2e6; }

    /* ✅ چیپ‌های انتخاب‌شده */
    .selected-chips-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 14px;
        margin-bottom: 12px;
    }
    .selected-chips-box .chips-title {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 8px;
    }
    .chips-list { display: flex; flex-wrap: wrap; gap: 6px; }
    .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #e7f1ff;
        color: #084298;
        border: 1px solid #b6d4fe;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .chip .chip-remove {
        background: transparent;
        border: none;
        color: #084298;
        cursor: pointer;
        padding: 0;
        font-size: 12px;
        line-height: 1;
    }
    .chip .chip-remove:hover { color: #dc3545; }
    .chip-customer {
        background: #d1e7dd;
        color: #0f5132;
        border-color: #badbcc;
    }
    .chip-customer .chip-remove { color: #0f5132; }
    .chip-customer .chip-remove:hover { color: #dc3545; }

    .filter-box {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 20px;
    }
    .filter-box .filter-title {
        font-size: 13px;
        font-weight: bold;
        color: #1e3a5f;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f1f3f5;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">📊 آمار فروش محصولات (ماهیانه)</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('dashboard')); ?>">داشبورد</a></li>
            <li class="breadcrumb-item active">آمار فروش محصولات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
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

        
        
        
        <div class="row g-3 mb-4">

            
            <div class="col-md-6">
                <div class="filter-box h-100">
                    <div class="filter-title">
                        <i class="fas fa-box me-1"></i>
                        محصولات انتخاب‌شده
                    </div>

                    
                    <form action="<?php echo e(route('product-sales-stats.add')); ?>" method="POST" class="d-flex gap-2 mb-3">
                        <?php echo csrf_field(); ?>
                        <select name="product_id" class="form-control product-search-select" style="width: 100%;" required>
                            <option value="">جستجو و انتخاب محصول...</option>
                            <?php $__currentLoopData = $allProductsList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($product->id); ?>"><?php echo e($product->name); ?> (<?php echo e($product->code); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" style="min-width: 80px;">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>

                    
                    <?php if($selectedProducts->count()): ?>
                        <div class="selected-chips-box">
                            <div class="chips-title">
                                <i class="fas fa-check-circle me-1"></i>
                                <?php echo e($selectedProducts->count()); ?> محصول انتخاب شده
                            </div>
                            <div class="chips-list">
                                <?php $__currentLoopData = $selectedProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="chip">
                                        <?php echo e($sp->name); ?>

                                        <form action="<?php echo e(route('product-sales-stats.remove')); ?>" method="POST" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="product_id" value="<?php echo e($sp->id); ?>">
                                            <button type="submit" class="chip-remove" title="حذف"
                                                    onclick="return confirm('حذف شود؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light mb-0" style="font-size: 13px;">
                            هیچ محصولی انتخاب نشده است.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="col-md-6">
                <div class="filter-box h-100">
                    <div class="filter-title">
                        <i class="fas fa-users me-1"></i>
                        مشتری‌های انتخاب‌شده
                        <span class="text-muted fw-normal" style="font-size: 11px;">
                            (اگه خالی باشه، همه مشتری‌ها نشون داده می‌شن)
                        </span>
                    </div>

                    
                    <form action="<?php echo e(route('product-sales-stats.add-customer')); ?>" method="POST" class="d-flex gap-2 mb-3">
                        <?php echo csrf_field(); ?>
                        <select name="customer_id" class="form-control customer-search-select" style="width: 100%;" required>
                            <option value="">جستجو و انتخاب مشتری...</option>
                            <?php $__currentLoopData = $allCustomersList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($customer->id); ?>"><?php echo e($customer->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm" style="min-width: 80px;">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>

                    
                    <?php if($selectedCustomers->count()): ?>
                        <div class="selected-chips-box">
                            <div class="chips-title">
                                <i class="fas fa-check-circle me-1"></i>
                                <?php echo e($selectedCustomers->count()); ?> مشتری انتخاب شده
                            </div>
                            <div class="chips-list">
                                <?php $__currentLoopData = $selectedCustomers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="chip chip-customer">
                                        <?php echo e($sc->name); ?>

                                        <form action="<?php echo e(route('product-sales-stats.remove-customer')); ?>" method="POST" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="customer_id" value="<?php echo e($sc->id); ?>">
                                            <button type="submit" class="chip-remove" title="حذف"
                                                    onclick="return confirm('حذف شود؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light mb-0" style="font-size: 13px;">
                            هیچ مشتری انتخاب نشده — همه مشتری‌ها در گزارش نمایش داده می‌شن.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        
        
        <form method="GET" action="<?php echo e(route('product-sales-stats.index')); ?>" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    <?php for($y = $currentYear - 2; $y <= $currentYear; $y++): ?>
                        <option value="<?php echo e($y); ?>" <?php echo e($year == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    <?php for($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo e($m); ?>" <?php echo e($month == $m ? 'selected' : ''); ?>><?php echo e($monthNames[$m - 1]); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">نمایش آمار</button>
            </div>
        </form>

        
        
        
        <?php if($reportData->count()): ?>
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0">
                    نتایج (<?php echo e($reportData->count()); ?> محصول
                    <?php if($selectedCustomers->count()): ?>
                        — فیلترشده برای <?php echo e($selectedCustomers->count()); ?> مشتری
                    <?php endif; ?>
                    )
                </h5>
                <?php if($reportData->count() >= 2): ?>
                    <span class="reorder-notice">
                        <i class="fas fa-arrows-alt me-1"></i>
                        برای تغییر ترتیب محصولات، از آیکون <strong>⋮⋮</strong> بکشید
                    </span>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle column-drag">
                                <i class="fas fa-grip-vertical"></i>
                            </th>
                            <th rowspan="2" class="align-middle">نام محصول</th>
                            <th rowspan="2" class="align-middle">نام مشتری</th>
                            <th colspan="2" class="text-center">رسمی</th>
                            <th colspan="2" class="text-center">غیر رسمی</th>
                            <th colspan="2" class="text-center">مجموع</th>
                        </tr>
                        <tr>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ (ریال)</th>
                        </tr>
                    </thead>
                    <tbody id="statsTableBody">
                        <?php $__currentLoopData = $reportData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $productItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $customers = $productItem->customers;
                                $customersCount = count($customers);
                                $firstRow = true;
                            ?>

                            <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="product-row <?php echo e($firstRow ? 'customer-first-row' : ''); ?>"
                                    data-product-id="<?php echo e($productItem->product_id); ?>">

                                    <?php if($firstRow): ?>
                                        <td rowspan="<?php echo e($customersCount); ?>" class="column-drag align-middle">
                                            <?php if($reportData->count() >= 2): ?>
                                                <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="drag-handle disabled">
                                                    <i class="fas fa-grip-vertical"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td rowspan="<?php echo e($customersCount); ?>" class="product-name-cell align-middle">
                                            <?php echo e($productItem->product_name); ?>

                                            <br>
                                            <small class="text-muted fw-normal">
                                                (<?php echo e($customersCount); ?> مشتری)
                                            </small>
                                        </td>
                                    <?php endif; ?>

                                    <td class="customer-cell"><?php echo e($customer['name']); ?></td>

                                    <td class="text-center"><?php echo e(number_format($customer['formal_total'])); ?></td>
                                    <td class="text-center"><?php echo e(number_format($customer['formal_amount'])); ?></td>
                                    <td class="text-center"><?php echo e(number_format($customer['informal_total'])); ?></td>
                                    <td class="text-center"><?php echo e(number_format($customer['informal_amount'])); ?></td>
                                    <td class="text-center fw-bold"><?php echo e(number_format($customer['total_quantity'])); ?></td>
                                    <td class="text-center fw-bold"><?php echo e(number_format($customer['total_amount'])); ?></td>
                                </tr>
                                <?php $firstRow = false; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            <tr class="table-light">
                                <td class="text-center fw-bold" colspan="2" style="font-size: 12px;">
                                    جمع <?php echo e($productItem->product_name); ?>

                                </td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->formal_total)); ?></td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->formal_amount)); ?></td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->informal_total)); ?></td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->informal_amount)); ?></td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->total_quantity)); ?></td>
                                <td class="text-center fw-bold"><?php echo e(number_format($productItem->total_amount)); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td colspan="3" class="text-center">مجموع کل</td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('formal_total'))); ?></td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('formal_amount'))); ?></td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('informal_total'))); ?></td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('informal_amount'))); ?></td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('total_quantity'))); ?></td>
                            <td class="text-center"><?php echo e(number_format($reportData->sum('total_amount'))); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                <?php if($selectedProducts->count() > 0): ?>
                    هیچ فروشی در این ماه برای محصولات و مشتری‌های انتخابی یافت نشد.
                <?php else: ?>
                    هیچ محصولی به لیست اضافه نشده است. از پنل بالا محصول مورد نظر را اضافه کنید.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
    var REORDER_URL = '<?php echo e(route("product-sales-stats.reorder")); ?>';

    $(document).ready(function() {
        // Select2 برای محصولات
        $('.product-search-select').select2({
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true, width: '100%', minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'محصولی یافت نشد'; }
            }
        });

        // Select2 برای مشتری‌ها
        $('.customer-search-select').select2({
            placeholder: 'جستجو و انتخاب مشتری...',
            allowClear: true, width: '100%', minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'مشتری یافت نشد'; }
            }
        });
    });

    // Drag & Drop
    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('statsTableBody');
        if (!tbody) return;

        var productIds = new Set();
        tbody.querySelectorAll('tr[data-product-id]').forEach(function(tr) {
            productIds.add(tr.getAttribute('data-product-id'));
        });

        if (productIds.size < 2) return;

        new Sortable(tbody, {
            draggable: 'tr.product-row',
            handle: '.drag-handle',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            filter: function(evt, target) {
                return !target.querySelector('.drag-handle:not(.disabled)');
            },
            onEnd: function(evt) {
                rearrangeProductGroups();
                saveOrder();
            }
        });
    });

    function rearrangeProductGroups() {
        var tbody = document.getElementById('statsTableBody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var groups = [];
        var currentGroup = null;

        rows.forEach(function(tr) {
            var pid = tr.getAttribute('data-product-id');
            if (pid) {
                currentGroup = { productId: pid, rows: [tr] };
                groups.push(currentGroup);
            } else if (currentGroup) {
                currentGroup.rows.push(tr);
            } else {
                groups.push({ productId: null, rows: [tr] });
            }
        });

        tbody.innerHTML = '';
        groups.forEach(function(g) {
            g.rows.forEach(function(tr) { tbody.appendChild(tr); });
        });
    }

    function saveOrder() {
        var rows = document.querySelectorAll('#statsTableBody tr[data-product-id]');
        var order = [];
        var seen = new Set();

        rows.forEach(function(row) {
            var id = row.getAttribute('data-product-id');
            if (id && !seen.has(id)) {
                seen.add(id);
                order.push(parseInt(id));
            }
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
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) showToast('✅ ترتیب ذخیره شد.', '#198754');
            else showToast('خطا: ' + (data.error || 'نامشخص'), '#dc3545');
        })
        .catch(function(err) {
            console.error(err);
            showToast('خطا در ذخیره.', '#dc3545');
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
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/reports/product-sales-stats.blade.php ENDPATH**/ ?>