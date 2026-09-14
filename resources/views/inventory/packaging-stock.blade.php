@extends('layouts.app')

@push('styles')
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
    .drag-handle.disabled { cursor: not-allowed; opacity: 0.25; }

    .sortable-ghost { background: #cfe2ff !important; opacity: 0.5; }
    .sortable-chosen { background: #e7f1ff !important; }

    .editable-cell { position: relative; padding: 6px 4px; }
    .editable-cell .cell-value { font-weight: 600; color: #212529; }
    .editable-cell .btn-edit-cell {
        display: inline-flex; align-items: center; justify-content: center;
        width: 22px; height: 22px; padding: 0; font-size: 10px;
        border-radius: 50%; margin-right: 4px;
        opacity: 0; transition: opacity 0.2s, transform 0.2s;
        background: transparent; border: 1px solid #0d6efd;
        color: #0d6efd; cursor: pointer; vertical-align: middle;
    }
    .editable-cell:hover .btn-edit-cell { opacity: 1; }
    .editable-cell .btn-edit-cell:hover {
        background: #0d6efd; color: #fff; transform: scale(1.15);
    }
    @media (max-width: 991px) { .editable-cell .btn-edit-cell { opacity: 0.6; } }

    .reorder-notice {
        background: #e7f1ff; color: #084298;
        padding: 6px 14px; border-radius: 8px;
        font-size: 12px; font-weight: 600;
        border: 1px solid #b6d4fe;
        display: inline-block; margin-bottom: 12px;
    }

    .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 6px;
        font-size: 12px; font-weight: 600;
    }
    .type-badge.carton { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
    .type-badge.layer  { background: #cff4fc; color: #055160; border: 1px solid #b6effb; }

    .edit-stock-packaging-name {
        background: #f1f3f5; padding: 10px 14px;
        border-radius: 8px; font-weight: bold; margin-bottom: 14px;
    }
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
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var REORDER_URL = '{{ route("inventory.packaging-stock.reorder") }}';
    var UPDATE_STOCK_URL_TEMPLATE = '{{ route("inventory.packaging-stock.update-stock", ["packaging" => 0]) }}';
    var HAS_SEARCH_FILTER = {{ request('search') ? 'true' : 'false' }};

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
    function getCurrentValueFromTable(packagingId) {
        var row = document.querySelector('tr[data-packaging-id="' + packagingId + '"]');
        if (!row) return 0;

        var cell = row.querySelector('.cell-value');
        if (!cell) return 0;

        var text = cell.textContent.trim().replace(/,/g, '').replace(/[۰-۹]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1776);
        }).replace(/[٠-٩]/g, function(d) {
            return String.fromCharCode(d.charCodeAt(0) - 1584);
        });

        var num = parseFloat(text);
        return isNaN(num) ? 0 : num;
    }

    function openEditStockModal(packagingId, packagingName, packagingType, currentStock) {
        document.getElementById('editStockPackagingId').value = packagingId;

        var typeLabel = (packagingType === 'carton') ? 'کارتن' : 'لایه';
        document.getElementById('editStockPackagingName').innerHTML =
            '<i class="fas fa-box me-2"></i>' + packagingName +
            ' <small class="text-muted">(' + typeLabel + ')</small>';

        // ✅ مقدار رو از سلول جدول می‌خونیم (نه از onclick)
        var tableValue = getCurrentValueFromTable(packagingId);
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
            var packagingId = document.getElementById('editStockPackagingId').value;
            var currentVal = getCurrentValueFromTable(packagingId);
            input.value = formatNumber(currentVal);
        }

        input.focus();
    }

    function savePackagingStock() {
        var packagingId = document.getElementById('editStockPackagingId').value;
        var mode = document.querySelector('input[name="editMode"]:checked').value;
        var input = document.getElementById('editStockInput');
        var saveBtn = document.getElementById('editStockSaveBtn');
        var errorBox = document.getElementById('editStockError');

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

        var url = UPDATE_STOCK_URL_TEMPLATE.replace(/\/0\/update-stock$/, '/' + packagingId + '/update-stock');

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
                errorBox.textContent = errMsg;
                errorBox.style.display = 'block';
                return;
            }

            var data = result.data;

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
                savePackagingStock();
            }
        });
    });
</script>
@endpush

@section('content')
@php
    $hasSearch = request('search') ? true : false;
    $rowCount = $packagings->count();
@endphp

<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">کارتن و لایه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        <form action="{{ route('inventory.packaging-stock') }}" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select product-search-select" style="width: 100%;">
                        <option value="">همه کارتن‌ها و لایه‌ها...</option>
                        @foreach(\App\Models\Packaging::orderBy('type')->orderBy('name')->get() as $packaging)
                            <option value="{{ $packaging->id }}" {{ request('search') == $packaging->id ? 'selected' : '' }}>
                                {{ $packaging->type == 'carton' ? '[کارتن]' : '[لایه]' }} {{ $packaging->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    @if($hasSearch)
                        <a href="{{ route('inventory.packaging-stock') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    @endif
                </div>
            </div>
        </form>

        @if(!$hasSearch && $rowCount >= 2)
            <div class="reorder-notice">
                <i class="fas fa-arrows-alt me-1"></i>
                برای تغییر ترتیب، ردیف‌ها را از آیکون <strong>⋮⋮</strong> بکشید — برای ویرایش روی <strong>✏️</strong> بزنید
            </div>
        @endif

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
                    @forelse($packagings as $packaging)
                    <tr data-packaging-id="{{ $packaging->id }}">

                        <td class="column-drag">
                            @if(!$hasSearch && $rowCount >= 2)
                                <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            @else
                                <div class="drag-handle disabled">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                            @endif
                        </td>

                        <td>
                            @if($packaging->type == 'carton')
                                <span class="type-badge carton">
                                    <i class="fas fa-box"></i> کارتن
                                </span>
                            @else
                                <span class="type-badge layer">
                                    <i class="fas fa-layer-group"></i> لایه
                                </span>
                            @endif
                        </td>

                        <td>{{ $packaging->name }}</td>

                        <td class="text-center editable-cell">
                            <span class="cell-value">{{ number_format($packaging->stock) }}</span>
                            <button type="button"
                                    class="btn-edit-cell"
                                    onclick="openEditStockModal(
                                        {{ $packaging->id }},
                                        '{{ addslashes($packaging->name) }}',
                                        '{{ $packaging->type }}',
                                        {{ (int) $packaging->stock }}
                                    )"
                                    title="ویرایش موجودی">
                                <i class="fas fa-pen"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                            @if($hasSearch)
                                موردی با این شناسه یافت نشد.
                            @else
                                هیچ کارتن یا لایه‌ای تعریف نشده است.
                            @endif
                        </td>
                    </tr>
                    @endforelse
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

                {{-- ✅ Toggle حالت ویرایش --}}
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

                {{-- ✅ نمایش مقدار فعلی (فقط در حالت adjust) --}}
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
                        onclick="savePackagingStock()">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
@endsection