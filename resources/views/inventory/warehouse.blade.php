@extends('layouts.app')

@push('styles')
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var UPDATE_STOCK_URL_TEMPLATE = '{{ route("inventory.warehouse.update-stock", ["product" => 0]) }}';

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

    // فرمت‌دهی عدد با ویرگول
    function formatNumber(value) {
        var num = String(value).replace(/,/g, '');
        if (num === '' || isNaN(num)) return '0';
        var parts = num.split('.');
        var integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.length > 1 ? integerPart + '.' + parts[1] : integerPart;
    }

    function openEditStockModal(productId, productName, productCode, currentStock) {
        document.getElementById('editStockProductId').value = productId;
        document.getElementById('editStockProductName').innerHTML =
            productName + '<br><small>کد: ' + productCode + '</small>';
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

    function saveWarehouseStock() {
        var productId  = document.getElementById('editStockProductId').value;
        var input      = document.getElementById('editStockInput');
        var saveBtn    = document.getElementById('editStockSaveBtn');
        var errorBox   = document.getElementById('editStockError');
        var rawValue   = toLatinDigits(input.value).replace(/[^0-9.]/g, '');

        if (rawValue === '' || isNaN(rawValue)) {
            errorBox.textContent = 'عدد معتبر وارد کنید.';
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
    //  ✅ فرمت‌دهی زنده با حفظ مکان‌نما
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('editStockInput');
        if (!input) return;

        input.addEventListener('input', function() {
            var cursorPos = this.selectionStart;
            var valueBeforeCursor = this.value.substring(0, cursorPos);

            // تعداد ارقام عددی قبل از مکان‌نما
            var digitsBeforeCursor = toLatinDigits(valueBeforeCursor).replace(/[^0-9]/g, '').length;

            // فقط اعداد لاتین
            var raw = toLatinDigits(this.value).replace(/[^0-9]/g, '');

            // فرمت کن
            var formatted = raw === '' ? '' : formatNumber(raw);
            this.value = formatted;

            // پیدا کردن مکان‌نمای جدید: بعد از همون تعداد رقم
            if (digitsBeforeCursor === 0) {
                this.setSelectionRange(0, 0);
                return;
            }

            var newCursor = 0;
            var digitCount = 0;
            for (var i = 0; i < formatted.length; i++) {
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
@endpush

@section('content')
@php
    $showHidden = request('show_hidden') == '1';
    $rowCount = count($inventories);
@endphp

<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی انبار</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">موجودی</a></li>
            <li class="breadcrumb-item active">موجودی انبار</li>
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

        <form action="{{ route('inventory.warehouse') }}" method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <select name="search" class="form-select product-search-select" style="width: 100%;">
                        <option value="">همه محصولات...</option>
                        @foreach(\App\Models\Product::where('status', 1)->orderBy('name')->get() as $product)
                            <option value="{{ $product->id }}" {{ request('search') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->code }})
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> جستجو
                    </button>
                    @if(request('search'))
                        <a href="{{ route('inventory.warehouse') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> پاک کردن
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="filter-bar">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge-count">{{ $rowCount }} محصول</span>
                @if($showHidden)
                    <span class="badge-count-hidden">
                        <i class="fas fa-eye me-1"></i> حالت نمایش همه
                    </span>
                @endif
                <span class="text-muted small">
                    — برای ویرایش موجودی روی ✏️ و برای حذف از گزارش روی ❌ بزنید
                </span>
            </div>

            <div>
                @if($showHidden)
                    <a href="{{ route('inventory.warehouse') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-eye-slash me-1"></i>
                        پنهان کردن محصولات مخفی‌شده
                    </a>
                @else
                    <a href="{{ route('inventory.warehouse', ['show_hidden' => 1]) }}" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-eye me-1"></i>
                        نمایش محصولات مخفی‌شده
                    </a>
                @endif
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
                    @forelse($inventories as $item)
                        @php
                            $isHidden = (bool) $item['product']->hidden_from_warehouse;
                        @endphp
                        <tr data-product-id="{{ $item['product']->id }}" class="{{ $isHidden ? 'hidden-row' : '' }}">
                            <td>
                                <div class="fw-bold">{{ $item['product']->name }}</div>
                                <small class="text-muted">کد: {{ $item['product']->code }}</small>
                                @if($isHidden)
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-eye-slash me-1"></i>مخفی
                                    </span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="stock-cell">
                                    <div class="stock-main">
                                        <span class="stock-value">{{ number_format($item['stock']) }}</span>
                                        <small>عدد</small>
                                    </div>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-edit-stock"
                                            onclick="openEditStockModal(
                                                {{ $item['product']->id }},
                                                '{{ addslashes($item['product']->name) }}',
                                                '{{ addslashes($item['product']->code) }}',
                                                {{ $item['stock'] }}
                                            )"
                                            title="ویرایش موجودی">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                </div>
                            </td>

                            <td class="text-center">
                                @if($item['per_box'] && $item['per_box'] > 0)
                                    <span class="stat-badge box" data-bs-toggle="tooltip"
                                          title="هر کارتن {{ number_format($item['per_box']) }} عدد">
                                        <i class="fas fa-box"></i>
                                        <span class="stat-box-value">{{ number_format($item['cartons']) }}</span>
                                    </span>
                                @else
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در کارتن تعریف نشده">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($item['per_pack'] && $item['per_pack'] > 0)
                                    <span class="stat-badge pack" data-bs-toggle="tooltip"
                                          title="هر بسته {{ number_format($item['per_pack']) }} عدد">
                                        <i class="fas fa-cube"></i>
                                        <span class="stat-pack-value">{{ number_format($item['packs']) }}</span>
                                    </span>
                                @else
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در بسته تعریف نشده">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($item['per_pallet'] && $item['per_pallet'] > 0)
                                    <span class="stat-badge pallet" data-bs-toggle="tooltip"
                                          title="هر پالت {{ number_format($item['per_pallet']) }} عدد">
                                        <i class="fas fa-pallet"></i>
                                        <span class="stat-pallet-value">{{ number_format($item['pallets']) }}</span>
                                    </span>
                                @else
                                    <span class="stat-badge dash" data-bs-toggle="tooltip"
                                          title="تعداد در پالت تعریف نشده">—</span>
                                @endif
                            </td>

                            <td class="column-action">
                                @if($isHidden)
                                    <form action="{{ route('inventory.warehouse.unhide', $item['product']) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('این محصول به موجودی انبار برگردانده شود؟')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success btn-icon" title="برگرداندن">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('inventory.warehouse.hide', $item['product']) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('این محصول از موجودی انبار حذف شود؟\n(در دیتابیس می‌ماند)')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="حذف از این گزارش">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                هیچ محصولی یافت نشد.
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
                    ویرایش موجودی انبار
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="edit-stock-product-name" id="editStockProductName"></div>
                <input type="hidden" id="editStockProductId">

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
                        onclick="saveWarehouseStock()">
                    <i class="fas fa-save me-1"></i> ذخیره
                </button>
            </div>
        </div>
    </div>
</div>
@endsection