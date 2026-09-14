

<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .hidden-row {
        background: #fff3cd !important;
        opacity: 0.75;
    }
    .hidden-row:hover { opacity: 1; }

    .btn-icon {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: 13px;
    }
    .column-action { width: 100px; text-align: center; }

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

    .stat-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px; border-radius: 6px;
        font-size: 13px; font-weight: 600;
        min-width: 70px; justify-content: center;
    }
    .stat-badge.pack   { background: #e7f1ff; color: #0d6efd; border: 1px solid #cfe2ff; }
    .stat-badge.box    { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .stat-badge.pallet { background: #fff3cd; color: #664d03; border: 1px solid #ffecb5; }
    .stat-badge.dash   { background: #f1f3f5; color: #adb5bd; border: 1px solid #e9ecef; }

    .stock-cell { display: inline-flex; align-items: center; gap: 8px; }
    .stock-main { font-size: 16px; font-weight: bold; color: #212529; }
    .stock-main small { font-size: 11px; color: #6c757d; font-weight: normal; }
    .btn-edit-stock {
        width: 28px; height: 28px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50%; font-size: 12px;
    }

    .edit-stock-product-name {
        background: #f1f3f5; padding: 10px 14px;
        border-radius: 8px; font-weight: bold; margin-bottom: 14px;
    }
    .edit-stock-product-name small { color: #6c757d; font-weight: normal; font-size: 12px; }
    .edit-stock-input {
        font-size: 22px; font-weight: bold; text-align: center;
        direction: ltr; font-family: 'Courier New', monospace; min-height: 50px;
    }
    .edit-stock-hint { font-size: 12px; color: #6c757d; margin-top: 6px; }

    /* ✅ استایل toggle حالت ویرایش */
    .mode-toggle-wrapper {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 14px;
        background: #f8f9fa;
        margin-bottom: 14px;
    }
    .mode-toggle-wrapper .btn-group { width: 100%; }
    .mode-toggle-wrapper .btn { flex: 1; font-weight: 600; }

    /* ✅ استایل input در حالت adjust */
    .edit-stock-input.mode-adjust {
        border-color: #198754 !important;
        background: #f0fff4 !important;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15) !important;
    }

    /* ✅ نمایش مقدار فعلی */
    .current-stock-info {
        background: #fffbea;
        border: 1px solid #ffe58f;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
        margin-top: 8px;
    }
    .current-stock-info .value {
        font-weight: bold;
        color: #b8860b;
        direction: ltr;
        display: inline-block;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
    var UPDATE_STOCK_URL_TEMPLATE = '<?php echo e(route("inventory.warehouse.update-stock", ["product" => 0])); ?>';

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

        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // تبدیل اعداد فارسی/عربی به لاتین
    function toLatinDigits(str) {
        return String(str).replace(/[۰-۹]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1776);
        }).replace(/[٠-٩]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1584);
        });
    }

    // ✅ formatNumber اصلاح‌شده: اعشار اضافی حذف میشه
    function formatNumber(value) {
        if (value === null || value === undefined || value === '') return '0';

        var num = parseFloat(String(value).replace(/,/g, ''));
        if (isNaN(num)) return '0';

        var str = Math.round(num).toString();
        var isNegative = str.startsWith('-');
        if (isNegative) str = str.substring(1);

        var integerPart = str.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return (isNegative ? '-' : '') + integerPart;
    }

    // ✅ مقدار فعلی رو از سلول جدول می‌خونه
    function getCurrentValueFromTable(productId) {
        var row = document.querySelector('tr[data-product-id="' + productId + '"]');
        if (!row) return 0;

        var cell = row.querySelector('.stock-value');
        if (!cell) return 0;

        var text = cell.textContent.trim().replace(/,/g, '').replace(/[۰-۹]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1776);
        }).replace(/[٠-٩]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1584);
        });

        var num = parseFloat(text);
        return isNaN(num) ? 0 : num;
    }

    function openEditStockModal(productId, productName, productCode, currentStock) {
        document.getElementById('editStockProductId').value = productId;
        document.getElementById('editStockProductName').innerHTML =
            productName + '<br><small>کد: ' + productCode + '</small>';

        // ✅ مقدار رو از سلول جدول می‌خونیم (نه از onclick)
        var tableValue = getCurrentValueFromTable(productId);
        var actualValue = (tableValue !== 0 || currentStock === 0) ? tableValue : currentStock;

        // ✅ ریست به حالت set
        document.getElementById('modeSet').checked = true;

        document.getElementById('editStockInput').value = formatNumber(actualValue);
        document.getElementById('editStockError').style.display = 'none';
        document.getElementById('currentStockValue').textContent = formatNumber(actualValue);

        updateModeUI();

        var modal = new bootstrap.Modal(document.getElementById('editStockModal'));
        modal.show();

        setTimeout(function() {
            var input = document.getElementById('editStockInput');
            input.focus();
            input.select();
        }, 400);
    }

    // ✅ تغییر UI بر اساس حالت انتخاب‌شده
    function updateModeUI() {
        var mode = document.querySelector('input[name="editMode"]:checked').value;
        var input = document.getElementById('editStockInput');
        var hint = document.getElementById('editStockHint');
        var currentInfo = document.getElementById('currentStockInfo');

        if (mode === 'adjust') {
            input.value = '';
            input.placeholder = 'مثلاً 200 یا -200';
            input.classList.add('mode-adjust');
            hint.innerHTML = '<i class="fas fa-info-circle me-1"></i> عدد مثبت = اضافه، عدد منفی = کسر';
            currentInfo.style.display = 'block';
        } else {
            input.placeholder = '0';
            input.classList.remove('mode-adjust');
            hint.innerHTML = '<i class="fas fa-info-circle me-1"></i> می‌توانید با اعداد فارسی یا انگلیسی وارد کنید.';
            currentInfo.style.display = 'none';

            // در حالت set، مقدار فعلی رو نشون بده
            var productId = document.getElementById('editStockProductId').value;
            var currentVal = getCurrentValueFromTable(productId);
            input.value = formatNumber(currentVal);
        }

        input.focus();
    }

    function saveWarehouseStock() {
        var productId  = document.getElementById('editStockProductId').value;
        var mode       = document.querySelector('input[name="editMode"]:checked').value;
        var input      = document.getElementById('editStockInput');
        var saveBtn    = document.getElementById('editStockSaveBtn');
        var errorBox   = document.getElementById('editStockError');

        // ✅ پاکسازی با حفظ علامت منفی
        var cleaned = toLatinDigits(input.value).trim().replace(/,/g, '');

        if (!/^-?\d+(\.\d+)?$/.test(cleaned)) {
            errorBox.textContent = 'عدد معتبر وارد کنید.';
            errorBox.style.display = 'block';
            return;
        }

        if (mode === 'set' && cleaned.startsWith('-')) {
            errorBox.textContent = 'در حالت «مقدار جدید»، عدد منفی مجاز نیست. لطفاً حالت «کسر / اضافه» را انتخاب کنید.';
            errorBox.style.display = 'block';
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال ذخیره...';
        errorBox.style.display = 'none';

        var url = UPDATE_STOCK_URL_TEMPLATE.replace(/\/0\/update-stock$/, '/' + productId + '/update-stock');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ quantity: cleaned, mode: mode }),
        })
        .then(function(response) {
            return response.json().then(function(data) {
                return { status: response.status, data: data };
            });
        })
        .then(function(result) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ذخیره';

            if (result.status !== 200 || !result.data.success) {
                var errMsg = result.data.error || result.data.message || 'خطا در ذخیره‌سازی (کد ' + result.status + ')';
                if (result.data.errors) {
                    var firstKey = Object.keys(result.data.errors)[0];
                    if (firstKey) errMsg = result.data.errors[firstKey][0];
                }
                errorBox.textContent = errMsg;
                errorBox.style.display = 'block';
                return;
            }

            var data = result.data;
            var row = document.querySelector('tr[data-product-id="' + productId + '"]');
            if (row) {
                row.querySelector('.stock-value').textContent = formatNumber(data.stock);
                var boxEl = row.querySelector('.stat-box-value');
                if (boxEl) boxEl.textContent = formatNumber(data.cartons);
                var packEl = row.querySelector('.stat-pack-value');
                if (packEl) packEl.textContent = formatNumber(data.packs);
                var palletEl = row.querySelector('.stat-pallet-value');
                if (palletEl) palletEl.textContent = formatNumber(data.pallets);

                row.style.transition = 'background-color 0.4s';
                row.style.backgroundColor = '#d1e7dd';
                setTimeout(function() { row.style.backgroundColor = ''; }, 800);
            }

            var modalEl = document.getElementById('editStockModal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            showToast(data.message || 'موجودی با موفقیت به‌روزرسانی شد.', '#198754');
        })
        .catch(function(err) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ذخیره';
            errorBox.textContent = 'خطای ارتباط با سرور: ' + err.message;
            errorBox.style.display = 'block';
            console.error(err);
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

    // ============================================================
    //  ✅ فرمت‌دهی زنده با حفظ مکان‌نما + پشتیبانی از منفی
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('editStockInput');
        if (!input) return;

        document.querySelectorAll('input[name="editMode"]').forEach(function(el) {
            el.addEventListener('change', updateModeUI);
        });

        input.addEventListener('input', function() {
            var mode = document.querySelector('input[name="editMode"]:checked').value;
            var allowNegative = (mode === 'adjust');

            var originalValue = this.value;
            var hasLeadingMinus = /^\s*-/.test(originalValue);
            if (!allowNegative) hasLeadingMinus = false;

            var cursorPos = this.selectionStart;
            var valueBeforeCursor = originalValue.substring(0, cursorPos);
            var digitsBeforeCursor = toLatinDigits(valueBeforeCursor).replace(/[^0-9]/g, '').length;

            var raw = toLatinDigits(originalValue).replace(/[^0-9]/g, '');

            var formatted;
            if (raw === '') {
                formatted = hasLeadingMinus ? '-' : '';
            } else {
                formatted = (hasLeadingMinus ? '-' : '') + formatNumber(raw);
            }

            this.value = formatted;

            if (raw === '') {
                var pos = hasLeadingMinus ? 1 : 0;
                try { this.setSelectionRange(pos, pos); } catch (e) {}
                return;
            }

            if (digitsBeforeCursor === 0) {
                var pos2 = hasLeadingMinus ? 1 : 0;
                try { this.setSelectionRange(pos2, pos2); } catch (e) {}
                return;
            }

            var newCursor = 0;
            var digitCount = 0;
            var startAt = hasLeadingMinus ? 1 : 0;
            for (var i = startAt; i < formatted.length; i++) {
                newCursor = i + 1;
                if (/[0-9]/.test(formatted[i])) {
                    digitCount++;
                    if (digitCount >= digitsBeforeCursor) break;
                }
            }

            try {
                this.setSelectionRange(newCursor, newCursor);
            } catch (e) {}
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveWarehouseStock();
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $showHidden = request('show_hidden') == '1';
    $rowCount = count($inventories);
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی انبار</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('inventory.index')); ?>">موجودی</a></li>
            <li class="breadcrumb-item active">موجودی انبار</li>
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

        <form action="<?php echo e(route('inventory.warehouse')); ?>" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select product-search-select" style="width: 100%;">
                        <option value="">همه محصولات...</option>
                        <?php $__currentLoopData = \App\Models\Product::where('status', 1)->orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($product->id); ?>" <?php echo e(request('search') == $product->id ? 'selected' : ''); ?>>
                                <?php echo e($product->name); ?> (<?php echo e($product->code); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    <?php if(request('search')): ?>
                        <a href="<?php echo e(route('inventory.warehouse')); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <div class="filter-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge-count"><?php echo e($rowCount); ?> محصول</span>
                <?php if($showHidden): ?>
                    <span class="badge-count-hidden">
                        <i class="fas fa-eye me-1"></i> حالت نمایش همه
                    </span>
                <?php endif; ?>
                <span class="text-muted small">
                    — برای ویرایش موجودی روی ✏️ و برای حذف از گزارش روی ❌ بزنید
                </span>
            </div>

            <div>
                <?php if($showHidden): ?>
                    <a href="<?php echo e(route('inventory.warehouse')); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye-slash me-1"></i>
                        پنهان کردن محصولات مخفی‌شده
                    </a>
                <?php else: ?>
                    <a href="<?php echo e(route('inventory.warehouse', ['show_hidden' => 1])); ?>" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye me-1"></i>
                        نمایش محصولات مخفی‌شده
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>نام محصول</th>
                        <th class="text-center">موجودی کل</th>
                        <th class="text-center"><i class="fas fa-box me-1"></i> کارتن</th>
                        <th class="text-center"><i class="fas fa-cube me-1"></i> بسته</th>
                        <th class="text-center"><i class="fas fa-pallet me-1"></i> پالت</th>
                        <th class="column-action">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $inventories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $isHidden = (bool) $item['product']->hidden_from_warehouse;
                        ?>
                        <tr data-product-id="<?php echo e($item['product']->id); ?>" class="<?php echo e($isHidden ? 'hidden-row' : ''); ?>">
                            <td>
                                <div class="fw-bold"><?php echo e($item['product']->name); ?></div>
                                <small class="text-muted">کد: <?php echo e($item['product']->code); ?></small>
                                <?php if($isHidden): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-eye-slash me-1"></i>مخفی
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <div class="stock-cell">
                                    <div class="stock-main">
                                        <span class="stock-value"><?php echo e(number_format($item['stock'])); ?></span>
                                        <small>عدد</small>
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-edit-stock"
                                            onclick="openEditStockModal(
                                                <?php echo e($item['product']->id); ?>,
                                                '<?php echo e(addslashes($item['product']->name)); ?>',
                                                '<?php echo e(addslashes($item['product']->code)); ?>',
                                                <?php echo e($item['stock']); ?>

                                            )"
                                            title="ویرایش موجودی">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </td>

                            <td class="text-center">
                                <?php if($item['per_box'] && $item['per_box'] > 0): ?>
                                    <span class="stat-badge box" data-bs-toggle="tooltip"
                                          title="هر کارتن <?php echo e(number_format($item['per_box'])); ?> عدد">
                                        <i class="fas fa-box"></i>
                                        <span class="stat-box-value"><?php echo e(number_format($item['cartons'])); ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در کارتن تعریف نشده">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php if($item['per_pack'] && $item['per_pack'] > 0): ?>
                                    <span class="stat-badge pack" data-bs-toggle="tooltip"
                                          title="هر بسته <?php echo e(number_format($item['per_pack'])); ?> عدد">
                                        <i class="fas fa-cube"></i>
                                        <span class="stat-pack-value"><?php echo e(number_format($item['packs'])); ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در بسته تعریف نشده">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php if($item['per_pallet'] && $item['per_pallet'] > 0): ?>
                                    <span class="stat-badge pallet" data-bs-toggle="tooltip"
                                          title="هر پالت <?php echo e(number_format($item['per_pallet'])); ?> عدد">
                                        <i class="fas fa-pallet"></i>
                                        <span class="stat-pallet-value"><?php echo e(number_format($item['pallets'])); ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در پالت تعریف نشده">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="column-action">
                                <?php if($isHidden): ?>
                                    <form action="<?php echo e(route('inventory.warehouse.unhide', $item['product'])); ?>"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('این محصول به موجودی انبار برگردانده شود؟')">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-success btn-icon" title="برگرداندن">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?php echo e(route('inventory.warehouse.hide', $item['product'])); ?>"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('این محصول از موجودی انبار حذف شود؟\n(در دیتابیس می‌ماند)')">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="حذف از این گزارش">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
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

<div class="modal fade" id="editStockModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-pen me-2"></i>
                    ویرایش موجودی انبار
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="edit-stock-product-name" id="editStockProductName"></div>
                <input type="hidden" id="editStockProductId">

                
                <div class="mode-toggle-wrapper">
                    <label class="form-label fw-bold mb-2">
                        <i class="fas fa-sliders-h me-1"></i> حالت ویرایش:
                    </label>
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="editMode" id="modeSet" value="set" checked>
                        <label class="btn btn-outline-primary" for="modeSet">
                            <i class="fas fa-pen me-1"></i> مقدار جدید
                        </label>

                        <input type="radio" class="btn-check" name="editMode" id="modeAdjust" value="adjust">
                        <label class="btn btn-outline-success" for="modeAdjust">
                            <i class="fas fa-exchange-alt me-1"></i> کسر / اضافه
                        </label>
                    </div>
                </div>

                <label class="form-label fw-bold">مقدار (عدد):</label>
                <input type="text"
                       id="editStockInput"
                       class="form-control edit-stock-input"
                       placeholder="0"
                       autocomplete="off">

                <div class="edit-stock-hint" id="editStockHint">
                    <i class="fas fa-info-circle me-1"></i>
                    می‌توانید با اعداد فارسی یا انگلیسی وارد کنید.
                </div>

                
                <div class="current-stock-info" id="currentStockInfo" style="display:none;">
                    <i class="fas fa-cube me-1 text-warning"></i>
                    مقدار فعلی:
                    <span class="value" id="currentStockValue">0</span>
                </div>

                <div id="editStockError" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> انصراف
                </button>
                <button type="button"
                        class="btn btn-primary"
                        id="editStockSaveBtn"
                        onclick="saveWarehouseStock()">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory/warehouse.blade.php ENDPATH**/ ?>