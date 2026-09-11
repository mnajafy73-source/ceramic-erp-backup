@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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

    .drag-handle.disabled {
        cursor: not-allowed;
        opacity: 0.25;
    }

    .sortable-ghost { background: #cfe2ff !important; opacity: 0.5; }
    .sortable-chosen { background: #e7f1ff !important; }

    /* فیلد جستجو */
    .search-wrapper {
        background: #fff;
        border-radius: 10px;
        padding: 14px 18px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
    }
    .search-wrapper .input-group {
        max-width: 600px;
    }

    /* سلول‌های قابل ویرایش */
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

    .locked-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        font-size: 10px;
        color: #adb5bd;
        margin-right: 4px;
        vertical-align: middle;
    }

    .auto-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        background: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #cfe2ff;
        margin-right: 4px;
        vertical-align: middle;
        font-weight: 600;
    }
    .manual-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        background: #fff3cd;
        color: #664d03;
        border: 1px solid #ffecb5;
        margin-right: 4px;
        vertical-align: middle;
        font-weight: 600;
    }

    /* Modal */
    .edit-cell-product-name {
        background: #f1f3f5;
        padding: 10px 14px;
        border-radius: 8px;
        font-weight: bold;
        margin-bottom: 14px;
    }
    .edit-cell-product-name small {
        color: #6c757d;
        font-weight: normal;
        font-size: 12px;
    }
    .edit-cell-field-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        background: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #cfe2ff;
        margin-bottom: 12px;
    }
    .edit-cell-input {
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        direction: ltr;
        font-family: 'Courier New', monospace;
        min-height: 50px;
    }
    .edit-cell-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }
    .btn-auto-reset {
        font-size: 12px;
        padding: 4px 12px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var REORDER_URL = '{{ route("inventory.all-stocks.reorder") }}';
    var UPDATE_FIELD_URL_TEMPLATE = '{{ route("inventory.all-stocks.update-field", ["product" => 0]) }}';
    var HAS_SEARCH_FILTER = {{ request('search') ? 'true' : 'false' }};

    // ============================================================
    //  Select2 برای جستجو
    // ============================================================
    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو یا انتخاب محصول...',
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
        if (HAS_SEARCH_FILTER) return;

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

    function saveAllStocksOrder() {
        var rows = document.querySelectorAll('#allStocksTableBody tr[data-product-id]');
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
            console.error('[AllStocks] Reorder error:', err);
            showToast('خطا در ذخیره ترتیب.', '#dc3545');
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
    //  باز کردن Modal ویرایش سلول
    // ============================================================
    function openEditCellModal(productId, productName, field, fieldLabel, currentValue, isManual) {
        document.getElementById('editCellProductId').value = productId;
        document.getElementById('editCellField').value = field;
        document.getElementById('editCellProductName').innerHTML =
            productName + '<br><small>کد: ' + document.getElementById('code-' + productId).textContent + '</small>';
        document.getElementById('editCellFieldLabel').textContent = fieldLabel;
        document.getElementById('editCellInput').value = formatNumber(currentValue);
        document.getElementById('editCellError').style.display = 'none';

        var autoBtn = document.getElementById('btnAutoReset');
        if (field === 'unpackaged' && isManual) {
            autoBtn.style.display = 'inline-block';
        } else {
            autoBtn.style.display = 'none';
        }

        var modal = new bootstrap.Modal(document.getElementById('editCellModal'));
        modal.show();

        setTimeout(function() {
            var input = document.getElementById('editCellInput');
            input.focus();
            input.select();
        }, 400);
    }

    function saveCellStock() {
        var productId = document.getElementById('editCellProductId').value;
        var field = document.getElementById('editCellField').value;
        var input = document.getElementById('editCellInput');
        var saveBtn = document.getElementById('editCellSaveBtn');
        var errorBox = document.getElementById('editCellError');
        var rawValue = toLatinDigits(input.value).replace(/[^0-9.]/g, '');

        if (rawValue === '' || isNaN(rawValue)) {
            errorBox.textContent = 'عدد معتبر وارد کنید.';
            errorBox.style.display = 'block';
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال ذخیره...';
        errorBox.style.display = 'none';

        sendUpdateRequest(productId, field, rawValue, saveBtn, errorBox);
    }

    function resetToAuto() {
        var productId = document.getElementById('editCellProductId').value;
        var field = document.getElementById('editCellField').value;
        var saveBtn = document.getElementById('editCellSaveBtn');
        var errorBox = document.getElementById('editCellError');

        if (!confirm('مقدار دستی حذف شود و به حالت محاسبه خودکار برگردد؟')) {
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال ذخیره...';
        errorBox.style.display = 'none';

        sendUpdateRequest(productId, field, 'auto', saveBtn, errorBox);
    }

    function sendUpdateRequest(productId, field, value, saveBtn, errorBox) {
        var url = UPDATE_FIELD_URL_TEMPLATE.replace(/\/0\/update-field$/, '/' + productId + '/update-field');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ field: field, quantity: value }),
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

            if (data.is_auto) {
                var modalEl = document.getElementById('editCellModal');
                var modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();

                showToast('✅ مقدار به حالت خودکار برگشت. صفحه در حال بازخوانی...', '#198754');
                setTimeout(function() { location.reload(); }, 1000);
                return;
            }

            var row = document.querySelector('tr[data-product-id="' + productId + '"]');
            if (row) {
                var cell = row.querySelector('[data-field="' + field + '"] .cell-value');
                if (cell) cell.textContent = formatNumber(data.stock);

                if (field === 'unpackaged') {
                    var badgeContainer = row.querySelector('[data-field="unpackaged"]');
                    if (badgeContainer) {
                        var existingBadge = badgeContainer.querySelector('.auto-badge, .manual-badge');
                        if (existingBadge) existingBadge.remove();

                        var newBadge = document.createElement('span');
                        newBadge.className = 'manual-badge';
                        newBadge.innerHTML = '<i class="fas fa-pen me-1"></i>دستی';
                        badgeContainer.appendChild(newBadge);
                    }
                }

                row.style.transition = 'background-color 0.4s';
                row.style.backgroundColor = '#d1e7dd';
                setTimeout(function() { row.style.backgroundColor = ''; }, 800);
            }

            var modalEl = document.getElementById('editCellModal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();

            showToast(data.message || 'موجودی با موفقیت به‌روزرسانی شد.', '#198754');
        })
        .catch(function(err) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> ذخیره';
            errorBox.textContent = 'خطای ارتباط با سرور: ' + err.message;
            errorBox.style.display = 'block';
            console.error('[AllStocks] Error:', err);
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

    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('editCellInput');
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
                    saveCellStock();
                }
            });
        }
    });
</script>
@endpush

@section('content')
@php
    $showHidden = request('show_hidden') == '1';
    $hasSearchFilter = request('search') ? true : false;
    $rowCount = $stocks->count();
@endphp

<div class="mb-4">
    <h4 class="fw-bold mb-1">گزارش جامع موجودی‌ها</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">گزارش جامع</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body">

        {{-- ✅ فیلد جستجو --}}
        <div class="search-wrapper">
            <form action="{{ route('inventory.all-stocks') }}" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                @if($showHidden)
                    <input type="hidden" name="show_hidden" value="1">
                @endif

                <div class="input-group flex-grow-1">
                    <span class="input-group-text">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <select name="search" class="form-select product-search-select">
                        <option value="">همه محصولات...</option>
                        @foreach(\App\Models\Product::where('status', 1)->orderBy('name')->get() as $product)
                            <option value="{{ $product->id }}" {{ request('search') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> جستجو
                    </button>
                    @if($hasSearchFilter)
                        <a href="{{ route('inventory.all-stocks', $showHidden ? ['show_hidden' => 1] : []) }}"
                           class="btn btn-outline-secondary"
                           title="پاک کردن فیلتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>

                <div>
                    @if($showHidden)
                        <a href="{{ route('inventory.all-stocks', $hasSearchFilter ? ['search' => request('search')] : []) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eye-slash me-1"></i>
                            پنهان کردن مخفی‌ها
                        </a>
                    @else
                        <a href="{{ route('inventory.all-stocks', array_merge($hasSearchFilter ? ['search' => request('search')] : [], ['show_hidden' => 1])) }}"
                           class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-eye me-1"></i>
                            نمایش مخفی‌ها
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- جدول --}}
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="column-drag">
                            <i class="fas fa-grip-vertical"></i>
                        </th>
                        <th>#</th>
                        <th>نام محصول</th>
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
                    @forelse($stocks as $index => $item)
                        @php
                            $isHidden = (bool) $item->product->hidden_from_all_stocks;
                            $hasChildren = $item->product->children()->where('status', 1)->exists();
                        @endphp
                        <tr data-product-id="{{ $item->product->id }}" class="{{ $isHidden ? 'hidden-row' : '' }}">

                            <td class="column-drag">
                                @if(!$hasSearchFilter && $rowCount >= 2)
                                    <div class="drag-handle" title="برای جابه‌جایی بکشید">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                @else
                                    <div class="drag-handle disabled">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                @endif
                            </td>

                            <td>{{ $loop->iteration }}</td>

                            <td>
                                {{ $item->product->name }}
                                <span id="code-{{ $item->product->id }}" style="display:none;">{{ $item->product->code }}</span>
                                @if($isHidden)
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-eye-slash me-1"></i>مخفی
                                    </span>
                                @endif
                            </td>

                            {{-- خام --}}
                            <td class="text-center editable-cell" data-field="raw">
                                <span class="cell-value">{{ number_format($item->raw) }}</span>
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'raw',
                                            'موجودی خام',
                                            {{ $item->raw }},
                                            false
                                        )"
                                        title="ویرایش موجودی خام">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- موم --}}
                            <td class="text-center editable-cell" data-field="wax">
                                <span class="cell-value">{{ number_format($item->wax) }}</span>
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'wax',
                                            'موجودی موم (۹۰۰°)',
                                            {{ $item->wax }},
                                            false
                                        )"
                                        title="ویرایش موجودی موم">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- شانه شده --}}
                            <td class="text-center editable-cell" data-field="shoulder">
                                <span class="cell-value">{{ number_format($item->shoulder) }}</span>
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'shoulder',
                                            'موجودی شانه شده',
                                            {{ $item->shoulder }},
                                            false
                                        )"
                                        title="ویرایش موجودی شانه شده">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- ضایعات موم --}}
                            <td class="text-center editable-cell" data-field="waste_mum">
                                <span class="cell-value">{{ number_format($item->waste_mum) }}</span>
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'waste_mum',
                                            'ضایعات موم',
                                            {{ $item->waste_mum }},
                                            false
                                        )"
                                        title="ویرایش ضایعات موم">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- ۱۳۰۰ درجه --}}
                            <td class="text-center editable-cell" data-field="glaze1300">
                                <span class="cell-value">{{ number_format($item->glaze1300) }}</span>
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'glaze1300',
                                            'موجودی ۱۳۰۰ درجه',
                                            {{ $item->glaze1300 }},
                                            false
                                        )"
                                        title="ویرایش موجودی ۱۳۰۰ درجه">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- بسته بندی نشده --}}
                            <td class="text-center editable-cell" data-field="unpackaged">
                                <span class="cell-value">{{ number_format($item->unpackaged ?? 0) }}</span>
                                @if($item->is_manual_unpackaged)
                                    <span class="manual-badge" title="مقدار دستی">
                                        <i class="fas fa-pen me-1"></i>دستی
                                    </span>
                                @else
                                    <span class="auto-badge" title="محاسبه‌شده از سیستم">
                                        <i class="fas fa-calculator me-1"></i>خودکار
                                    </span>
                                @endif
                                <button type="button"
                                        class="btn-edit-cell"
                                        onclick="openEditCellModal(
                                            {{ $item->product->id }},
                                            '{{ addslashes($item->product->name) }}',
                                            'unpackaged',
                                            'موجودی بسته بندی نشده',
                                            {{ $item->unpackaged ?? 0 }},
                                            {{ $item->is_manual_unpackaged ? 'true' : 'false' }}
                                        )"
                                        title="ویرایش موجودی بسته بندی نشده">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>

                            {{-- انبار --}}
                            <td class="text-center {{ $hasChildren ? '' : 'editable-cell' }}" data-field="warehouse">
                                <span class="cell-value">{{ number_format($item->warehouse) }}</span>
                                @if($hasChildren)
                                    <span class="locked-cell"
                                          title="این محصول فرزند دارد — برای ویرایش از صفحه موجودی انبار استفاده کنید">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                @else
                                    <button type="button"
                                            class="btn-edit-cell"
                                            onclick="openEditCellModal(
                                                {{ $item->product->id }},
                                                '{{ addslashes($item->product->name) }}',
                                                'warehouse',
                                                'موجودی انبار',
                                                {{ $item->warehouse }},
                                                false
                                            )"
                                            title="ویرایش موجودی انبار">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @endif
                            </td>

                            <td class="column-action">
                                @if($isHidden)
                                    <form action="{{ route('inventory.all-stocks.unhide', $item->product) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('این محصول به گزارش برگردانده شود؟')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-success btn-icon"
                                                title="برگرداندن به گزارش">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('inventory.all-stocks.hide', $item->product) }}"
                                          method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('این محصول از گزارش حذف شود؟\n(در دیتابیس می‌ماند و می‌توانید بعداً برگردانید)')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger btn-icon"
                                                title="حذف از این گزارش">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                @if($hasSearchFilter)
                                    محصولی با این فیلتر یافت نشد.
                                @else
                                    هیچ محصولی یافت نشد.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal ویرایش سلول --}}
<div class="modal fade" id="editCellModal" tabindex="-1" aria-hidden="true">
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

                <div class="edit-cell-product-name" id="editCellProductName"></div>

                <div class="edit-cell-field-badge" id="editCellFieldLabel"></div>

                <input type="hidden" id="editCellProductId">
                <input type="hidden" id="editCellField">

                <label class="form-label fw-bold">مقدار جدید:</label>
                <input type="text"
                       id="editCellInput"
                       class="form-control edit-cell-input"
                       placeholder="0"
                       autocomplete="off"
                       inputmode="numeric">

                <div class="edit-cell-hint">
                    <i class="fas fa-info-circle me-1"></i>
                    می‌توانید با اعداد فارسی یا انگلیسی وارد کنید.
                </div>

                <div class="mt-2">
                    <button type="button"
                            class="btn btn-outline-warning btn-auto-reset"
                            id="btnAutoReset"
                            style="display:none;"
                            onclick="resetToAuto()">
                        <i class="fas fa-undo me-1"></i>
                        برگشت به حالت خودکار
                    </button>
                </div>

                <div id="editCellError" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> انصراف
                </button>
                <button type="button"
                        class="btn btn-primary"
                        id="editCellSaveBtn"
                        onclick="saveCellStock()">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
@endsection