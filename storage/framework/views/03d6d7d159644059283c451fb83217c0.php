

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

    .editable-cell {
        position: relative;
        padding: 6px 4px;
    }
    .editable-cell .cell-value {
        font-weight: 600;
        color: #212529;
    }
    .editable-cell .btn-edit-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        padding: 0;
        font-size: 10px;
        border-radius: 50%;
        margin-right: 4px;
        opacity: 0;
        transition: opacity 0.2s, transform 0.2s;
        background: transparent;
        border: 1px solid #0d6efd;
        color: #0d6efd;
        cursor: pointer;
        vertical-align: middle;
    }
    .editable-cell:hover .btn-edit-cell { opacity: 1; }
    .editable-cell .btn-edit-cell:hover {
        background: #0d6efd;
        color: #fff;
        transform: scale(1.15);
    }
    @media (max-width: 991px) {
        .editable-cell .btn-edit-cell { opacity: 0.6; }
    }

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

    .type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    .type-badge.carton {
        background: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }
    .type-badge.layer {
        background: #cff4fc;
        color: #055160;
        border: 1px solid #b6effb;
    }

    .edit-stock-packaging-name {
        background: #f1f3f5;
        padding: 10px 14px;
        border-radius: 8px;
        font-weight: bold;
        margin-bottom: 14px;
    }
    .edit-stock-input {
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        direction: ltr;
        font-family: 'Courier New', monospace;
        min-height: 50px;
    }
    .edit-stock-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
    var REORDER_URL = '<?php echo e(route("inventory.packaging-stock.reorder")); ?>';
    var UPDATE_STOCK_URL_TEMPLATE = '<?php echo e(route("inventory.packaging-stock.update-stock", ["packaging" => 0])); ?>';
    var HAS_SEARCH_FILTER = <?php echo e(request('search') ? 'true' : 'false'); ?>;

    // ============================================================
    //  Select2
    // ============================================================
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'موردی یافت نشد'; }
            }
        });
    });

    // ============================================================
    //  Drag & Drop
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        if (HAS_SEARCH_FILTER) return;

        var tbody = document.getElementById('packagingsTableBody');
        if (!tbody) return;

        if (tbody.querySelectorAll('tr[data-packaging-id]').length < 2) return;

        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                savePackagingsOrder();
            }
        });
    });

    function savePackagingsOrder() {
        var rows = document.querySelectorAll('#packagingsTableBody tr[data-packaging-id]');
        var order = [];

        rows.forEach(function(row) {
            var id = row.getAttribute('data-packaging-id');
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

    // ============================================================
    //  تبدیل اعداد فارسی/عربی
    // ============================================================
    function toLatinDigits(str) {
        return String(str).replace(/[۰-۹]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1776);
        }).replace(/[٠-٩]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1584);
        });
    }

    function formatNumber(value) {
        var num = String(value).replace(/,/g, '');
        if (num === '' || isNaN(num)) return '0';
        var parts = num.split('.');
        var integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.length > 1 ? integerPart + '.' + parts[1] : integerPart;
    }

    // ============================================================
    //  باز کردن Modal ویرایش
    // ============================================================
    function openEditStockModal(packagingId, packagingName, packagingType, currentStock) {
        document.getElementById('editStockPackagingId').value = packagingId;

        var typeLabel = (packagingType === 'carton') ? 'کارتن' : 'لایه';
        document.getElementById('editStockPackagingName').innerHTML =
            '<i class="fas fa-box me-2"></i>' + packagingName +
            ' <small class="text-muted">(' + typeLabel + ')</small>';

        document.getElementById('editStockInput').value = formatNumber(currentStock);
        document.getElementById('editStockError').style.display = 'none';

        var modal = new bootstrap.Modal(document.getElementById('editStockModal'));
        modal.show();

        setTimeout(function() {
            var input = document.getElementById('editStockInput');
            input.focus();
            input.select();
        }, 400);
    }

    // ============================================================
    //  ذخیره مقدار
    // ============================================================
    function savePackagingStock() {
        var packagingId = document.getElementById('editStockPackagingId').value;
        var input = document.getElementById('editStockInput');
        var saveBtn = document.getElementById('editStockSaveBtn');
        var errorBox = document.getElementById('editStockError');
        var rawValue = toLatinDigits(input.value).replace(/[^0-9.]/g, '');

        if (rawValue === '' || isNaN(rawValue)) {
            errorBox.textContent = 'عدد معتبر وارد کنید.';
            errorBox.style.display = 'block';
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال ذخیره...';
        errorBox.style.display = 'none';

        var url = UPDATE_STOCK_URL_TEMPLATE.replace(/\/0\/update-stock$/, '/' + packagingId + '/update-stock');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ quantity: rawValue }),
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
                errorBox.textContent = errMsg;
                errorBox.style.display = 'block';
                return;
            }

            var data = result.data;

            // آپدیت سلول
            var row = document.querySelector('tr[data-packaging-id="' + packagingId + '"]');
            if (row) {
                var cell = row.querySelector('.cell-value');
                if (cell) cell.textContent = formatNumber(data.stock);

                row.style.transition = 'background-color 0.4s';
                row.style.backgroundColor = '#d1e7dd';
                setTimeout(function() { row.style.backgroundColor = ''; }, 800);
            }

            var modalEl = document.getElementById('editStockModal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            showToast(data.message || 'موجودی به‌روزرسانی شد.', '#198754');
        })
        .catch(function(err) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ذخیره';
            errorBox.textContent = 'خطای ارتباط: ' + err.message;
            errorBox.style.display = 'block';
            console.error(err);
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

    // فرمت‌دهی زنده ورودی
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('editStockInput');
        if (input) {
            input.addEventListener('input', function() {
                var cursor = this.selectionStart;
                var raw = toLatinDigits(this.value).replace(/[^0-9.]/g, '');
                this.value = formatNumber(raw);
                try { this.setSelectionRange(cursor, cursor); } catch(e) {}
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    savePackagingStock();
                }
            });
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $hasSearch = request('search') ? true : false;
    $rowCount = $packagings->count();
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('inventory.index')); ?>">موجودی</a></li>
            <li class="breadcrumb-item active">کارتن و لایه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        
        <form action="<?php echo e(route('inventory.packaging-stock')); ?>" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select product-search-select" style="width: 100%;">
                        <option value="">همه کارتن‌ها و لایه‌ها...</option>
                        <?php $__currentLoopData = \App\Models\Packaging::orderBy('type')->orderBy('name')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($packaging->id); ?>" <?php echo e(request('search') == $packaging->id ? 'selected' : ''); ?>>
                                <?php echo e($packaging->type == 'carton' ? '[کارتن]' : '[لایه]'); ?> <?php echo e($packaging->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    <?php if($hasSearch): ?>
                        <a href="<?php echo e(route('inventory.packaging-stock')); ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        
        <?php if(!$hasSearch && $rowCount >= 2): ?>
            <div class="reorder-notice">
                <i class="fas fa-arrows-alt me-1"></i>
                برای تغییر ترتیب، ردیف‌ها را از آیکون <strong>⋮⋮</strong> بکشید — برای ویرایش روی <strong>✏️</strong> بزنید
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="column-drag">
                            <i class="fas fa-grip-vertical"></i>
                        </th>
                        <th>نوع</th>
                        <th>نام</th>
                        <th class="text-center">موجودی (عدد)</th>
                    </tr>
                </thead>
                <tbody id="packagingsTableBody">
                    <?php $__empty_1 = true; $__currentLoopData = $packagings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $packaging): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr data-packaging-id="<?php echo e($packaging->id); ?>">

                        
                        <td class="column-drag">
                            <?php if(!$hasSearch && $rowCount >= 2): ?>
                                <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            <?php else: ?>
                                <div class="drag-handle disabled">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if($packaging->type == 'carton'): ?>
                                <span class="type-badge carton">
                                    <i class="fas fa-box"></i> کارتن
                                </span>
                            <?php else: ?>
                                <span class="type-badge layer">
                                    <i class="fas fa-layer-group"></i> لایه
                                </span>
                            <?php endif; ?>
                        </td>

                        <td><?php echo e($packaging->name); ?></td>

                        
                        <td class="text-center editable-cell">
                            <span class="cell-value"><?php echo e(number_format($packaging->stock)); ?></span>
                            <button type="button"
                                    class="btn-edit-cell"
                                    onclick="openEditStockModal(
                                        <?php echo e($packaging->id); ?>,
                                        '<?php echo e(addslashes($packaging->name)); ?>',
                                        '<?php echo e($packaging->type); ?>',
                                        <?php echo e((int) $packaging->stock); ?>

                                    )"
                                    title="ویرایش موجودی">
                                <i class="fas fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                            <?php if($hasSearch): ?>
                                موردی با این شناسه یافت نشد.
                            <?php else: ?>
                                هیچ کارتن یا لایه‌ای تعریف نشده است.
                            <?php endif; ?>
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
                    ویرایش موجودی
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="edit-stock-packaging-name" id="editStockPackagingName"></div>

                <input type="hidden" id="editStockPackagingId">

                <label class="form-label fw-bold">موجودی جدید (عدد):</label>
                <input type="text"
                       id="editStockInput"
                       class="form-control edit-stock-input"
                       placeholder="0"
                       autocomplete="off"
                       inputmode="numeric">

                <div class="edit-stock-hint">
                    <i class="fas fa-info-circle me-1"></i>
                    می‌توانید با اعداد فارسی یا انگلیسی وارد کنید.
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
                        onclick="savePackagingStock()">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory/packaging-stock.blade.php ENDPATH**/ ?>