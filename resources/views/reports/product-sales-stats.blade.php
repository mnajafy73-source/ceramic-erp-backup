@extends('layouts.app')

@push('styles')
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

    .customer-main-cell {
        background: #f8f9fa;
        font-weight: bold;
        color: #1e3a5f;
        font-size: 15px;
        vertical-align: middle !important;
        border-right: 4px solid #0d6efd !important;
    }

    .product-sub-cell {
        padding-right: 24px !important;
        color: #495057;
    }
    .product-sub-cell::before {
        content: '└';
        color: #adb5bd;
        margin-left: 6px;
        font-size: 13px;
    }

    .customer-first-row { border-top: 2px solid #adb5bd; }

    .paid-cell {
        background: #d1e7dd !important;
        font-weight: bold;
        color: #0a3622 !important;
        vertical-align: middle !important;
        text-align: center;
    }
    .remaining-cell {
        font-weight: bold;
        vertical-align: middle !important;
        text-align: center;
    }
    .remaining-cell.positive {
        background: #f8d7da !important;
        color: #58151c !important;
    }
    .remaining-cell.zero {
        background: #d1e7dd !important;
        color: #0a3622 !important;
    }
    .remaining-cell.negative {
        background: #fff3cd !important;
        color: #664d03 !important;
    }

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

    /* ✅ جدول با اسکرول داخلی و هدر ثابت */
    .stats-table-wrapper {
        max-height: 550px;
        overflow-y: auto;
        overflow-x: auto;
        border-radius: 0 0 8px 8px;
        position: relative;
    }

    .stats-table {
        font-size: 13px;
        margin-bottom: 0;
        vertical-align: middle;
    }
    .stats-table th, .stats-table td {
        vertical-align: middle;
    }

    /* ✅ هدر ثابت - ۲ ردیف */
    .stats-table thead tr:first-child th {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #212529 !important;
        color: #fff !important;
        border-bottom: 1px solid #000;
    }

    .stats-table thead tr:nth-child(2) th {
        position: sticky;
        top: 42px;
        z-index: 20;
        background: #212529 !important;
        color: #fff !important;
        border-bottom: 2px solid #000;
    }

    /* ✅ فوتر ثابت */
    .stats-table tfoot tr td {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: #e9ecef !important;
        border-top: 2px solid #adb5bd;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.15);
    }

    .customer-name-icon {
        color: #0d6efd;
        margin-left: 6px;
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📊 آمار فروش محصولات (ماهیانه)</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">آمار فروش محصولات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- پنل انتخاب --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="filter-box h-100">
                    <div class="filter-title">
                        <i class="fas fa-box me-1"></i>
                        محصولات انتخاب‌شده
                    </div>

                    <form action="{{ route('product-sales-stats.add') }}" method="POST" class="d-flex gap-2 mb-3">
                        @csrf
                        <select name="product_id" class="form-control product-search-select" style="width: 100%;" required>
                            <option value="">جستجو و انتخاب محصول...</option>
                            @foreach($allProductsList as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm" style="min-width: 80px;">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>

                    @if($selectedProducts->count())
                        <div class="selected-chips-box">
                            <div class="chips-title">
                                <i class="fas fa-check-circle me-1"></i>
                                {{ $selectedProducts->count() }} محصول انتخاب شده
                            </div>
                            <div class="chips-list">
                                @foreach($selectedProducts as $sp)
                                    <span class="chip">
                                        {{ $sp->name }}
                                        <form action="{{ route('product-sales-stats.remove') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $sp->id }}">
                                            <button type="submit" class="chip-remove" title="حذف"
                                                    onclick="return confirm('حذف شود؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light mb-0" style="font-size: 13px;">
                            هیچ محصولی انتخاب نشده است.
                        </div>
                    @endif
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

                    <form action="{{ route('product-sales-stats.add-customer') }}" method="POST" class="d-flex gap-2 mb-3">
                        @csrf
                        <select name="customer_id" class="form-control customer-search-select" style="width: 100%;" required>
                            <option value="">جستجو و انتخاب مشتری...</option>
                            @foreach($allCustomersList as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-success btn-sm" style="min-width: 80px;">
                            <i class="fas fa-plus"></i> افزودن
                        </button>
                    </form>

                    @if($selectedCustomers->count())
                        <div class="selected-chips-box">
                            <div class="chips-title">
                                <i class="fas fa-check-circle me-1"></i>
                                {{ $selectedCustomers->count() }} مشتری انتخاب شده
                            </div>
                            <div class="chips-list">
                                @foreach($selectedCustomers as $sc)
                                    <span class="chip chip-customer">
                                        {{ $sc->name }}
                                        <form action="{{ route('product-sales-stats.remove-customer') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="customer_id" value="{{ $sc->id }}">
                                            <button type="submit" class="chip-remove" title="حذف"
                                                    onclick="return confirm('حذف شود؟')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light mb-0" style="font-size: 13px;">
                            هیچ مشتری انتخاب نشده — همه مشتری‌ها در گزارش نمایش داده می‌شن.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- فیلتر ماه و سال --}}
        <form method="GET" action="{{ route('product-sales-stats.index') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    @for($y = $currentYear - 2; $y <= $currentYear; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $monthNames[$m - 1] }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">نمایش آمار</button>
            </div>
        </form>

        {{-- جدول --}}
        @if($reportData->count())
            @php
                $grandFormalQty = $reportData->sum('formal_qty');
                $grandFormalAmount = $reportData->sum('formal_amount');
                $grandInformalQty = $reportData->sum('informal_qty');
                $grandInformalAmount = $reportData->sum('informal_amount');
                $grandTotalQty = $reportData->sum('total_qty');
                $grandTotalAmount = $reportData->sum('total_amount');
                $grandPaid = $reportData->sum('paid_amount');
                $grandRemaining = $reportData->sum('remaining');
            @endphp

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0">
                    نتایج ({{ $reportData->count() }} مشتری)
                </h5>
                @if($reportData->count() >= 2)
                    <span class="reorder-notice">
                        <i class="fas fa-arrows-alt me-1"></i>
                        برای تغییر ترتیب مشتری‌ها، ردیف‌ها را از آیکون <strong>⋮⋮</strong> بکشید
                    </span>
                @endif
            </div>

            <div class="stats-table-wrapper">
                <table class="table table-bordered table-hover stats-table">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle column-drag">
                                <i class="fas fa-grip-vertical"></i>
                            </th>
                            <th rowspan="2" class="align-middle" style="min-width: 180px;">نام مشتری</th>
                            <th rowspan="2" class="align-middle" style="min-width: 180px;">نام محصول</th>
                            <th colspan="2" class="text-center">رسمی</th>
                            <th colspan="2" class="text-center">غیر رسمی</th>
                            <th colspan="2" class="text-center">مجموع</th>
                            <th rowspan="2" class="align-middle text-center" style="background:#198754; color:#fff;">
                                <i class="fas fa-check-circle me-1"></i>
                                پرداخت شده
                            </th>
                            <th rowspan="2" class="align-middle text-center" style="background:#dc3545; color:#fff;">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                مانده حساب
                            </th>
                        </tr>
                        <tr>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ</th>
                            <th class="text-center">تعداد</th>
                            <th class="text-center">مبلغ</th>
                        </tr>
                    </thead>
                    <tbody id="statsTableBody">
                        @foreach($reportData as $customerItem)
                            @php
                                $products = $customerItem->products;
                                $productsCount = count($products);
                                $firstRow = true;
                            @endphp

                            @foreach($products as $p)
                                <tr class="product-row {{ $firstRow ? 'customer-first-row' : '' }}"
                                    data-product-id="{{ $p['product_id'] }}">

                                    <td class="column-drag align-middle text-center">
                                        @if($firstRow && $reportData->count() >= 2)
                                            <div class="drag-handle" title="برای جابه‌جایی مشتری بکشید">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>
                                        @elseif($firstRow)
                                            <div class="drag-handle disabled">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>
                                        @endif
                                    </td>

                                    @if($firstRow)
                                        <td rowspan="{{ $productsCount }}" class="customer-main-cell">
                                            <i class="fas fa-user-circle customer-name-icon"></i>
                                            {{ $customerItem->customer_name }}
                                            <br>
                                            <small class="text-muted fw-normal" style="font-size: 11px;">
                                                ({{ $productsCount }} محصول)
                                            </small>
                                        </td>
                                    @endif

                                    <td class="product-sub-cell">
                                        {{ $p['product_name'] }}
                                    </td>

                                    <td class="text-center">{{ number_format($p['formal_qty']) }}</td>
                                    <td class="text-center">{{ number_format($p['formal_amount']) }}</td>
                                    <td class="text-center">{{ number_format($p['informal_qty']) }}</td>
                                    <td class="text-center">{{ number_format($p['informal_amount']) }}</td>
                                    <td class="text-center fw-bold">{{ number_format($p['total_qty']) }}</td>
                                    <td class="text-center fw-bold">{{ number_format($p['total_amount']) }}</td>

                                    @if($firstRow)
                                        <td rowspan="{{ $productsCount }}" class="paid-cell">
                                            <div style="font-size: 15px;">
                                                {{ number_format($customerItem->paid_amount) }}
                                            </div>
                                            <small class="text-muted fw-normal" style="font-size: 11px;">
                                                ریال
                                            </small>
                                        </td>

                                        @php
                                            $remaining = $customerItem->remaining;
                                            $remainingClass = 'zero';
                                            if ($remaining > 0) $remainingClass = 'positive';
                                            elseif ($remaining < 0) $remainingClass = 'negative';
                                        @endphp
                                        <td rowspan="{{ $productsCount }}" class="remaining-cell {{ $remainingClass }}">
                                            <div style="font-size: 15px;">
                                                {{ number_format($remaining) }}
                                            </div>
                                            <small class="fw-normal" style="font-size: 11px;">
                                                @if($remaining > 0)
                                                    بدهکار
                                                @elseif($remaining < 0)
                                                    بستانکار
                                                @else
                                                    تسویه
                                                @endif
                                            </small>
                                        </td>
                                    @endif
                                </tr>
                                @php $firstRow = false; @endphp
                            @endforeach

                            {{-- ✅ ردیف جمع مشتری - با colspan=2 برای پوشش ستون مشتری + محصول --}}
                            <tr class="table-light" style="border-bottom: 3px solid #adb5bd; font-weight: bold;">
                                <td></td>
                                <td colspan="2" class="text-center" style="font-size: 13px;">
                                    <i class="fas fa-calculator me-1"></i> جمع {{ $customerItem->customer_name }}
                                </td>
                                <td class="text-center">{{ number_format($customerItem->formal_qty) }}</td>
                                <td class="text-center">{{ number_format($customerItem->formal_amount) }}</td>
                                <td class="text-center">{{ number_format($customerItem->informal_qty) }}</td>
                                <td class="text-center">{{ number_format($customerItem->informal_amount) }}</td>
                                <td class="text-center">{{ number_format($customerItem->total_qty) }}</td>
                                <td class="text-center">{{ number_format($customerItem->total_amount) }}</td>
                                <td class="text-center" style="background:#d1e7dd; color:#0a3622;">
                                    {{ number_format($customerItem->paid_amount) }}
                                </td>
                                <td class="text-center
                                    @if($customerItem->remaining > 0) bg-danger bg-opacity-10 text-danger
                                    @elseif($customerItem->remaining < 0) bg-warning bg-opacity-10
                                    @else bg-success bg-opacity-10 text-success
                                    @endif">
                                    {{ number_format($customerItem->remaining) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr style="font-size: 14px;">
                            <td></td>
                            <td colspan="2" class="text-center">مجموع کل</td>
                            <td class="text-center">{{ number_format($grandFormalQty) }}</td>
                            <td class="text-center">{{ number_format($grandFormalAmount) }}</td>
                            <td class="text-center">{{ number_format($grandInformalQty) }}</td>
                            <td class="text-center">{{ number_format($grandInformalAmount) }}</td>
                            <td class="text-center">{{ number_format($grandTotalQty) }}</td>
                            <td class="text-center">{{ number_format($grandTotalAmount) }}</td>
                            <td class="text-center text-success">{{ number_format($grandPaid) }}</td>
                            <td class="text-center
                                @if($grandRemaining > 0) text-danger
                                @elseif($grandRemaining < 0) text-warning
                                @else text-success
                                @endif">
                                {{ number_format($grandRemaining) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-1"></i>
                هیچ فروشی در این ماه برای محصولات و مشتری‌های انتخابی یافت نشد.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var REORDER_URL = '{{ route("product-sales-stats.reorder") }}';

    $(document).ready(function() {
        $('.product-search-select').select2({
            placeholder: 'جستجو و انتخاب محصول...',
            allowClear: true, width: '100%', minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'محصولی یافت نشد'; }
            }
        });

        $('.customer-search-select').select2({
            placeholder: 'جستجو و انتخاب مشتری...',
            allowClear: true, width: '100%', minimumInputLength: 0,
            language: {
                searching: function() { return 'در حال جستجو...'; },
                noResults: function() { return 'مشتری یافت نشد'; }
            }
        });
    });

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
            handle: '.drag-handle:not(.disabled)',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                rearrangeCustomerGroups();
                saveOrder();
            }
        });
    });

    function rearrangeCustomerGroups() {
        var tbody = document.getElementById('statsTableBody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var groups = [];
        var currentGroup = null;

        rows.forEach(function(tr) {
            var isProductRow = tr.classList.contains('product-row');
            var isFirstRow = tr.classList.contains('customer-first-row');

            if (isProductRow && isFirstRow) {
                currentGroup = { rows: [tr] };
                groups.push(currentGroup);
            } else if (currentGroup) {
                currentGroup.rows.push(tr);
            } else {
                groups.push({ rows: [tr] });
            }
        });

        tbody.innerHTML = '';
        groups.forEach(function(g) {
            g.rows.forEach(function(tr) { tbody.appendChild(tr); });
        });

        fixRowspans(tbody);
    }

    function fixRowspans(tbody) {
        var rows = Array.from(tbody.querySelectorAll('tr.product-row'));
        var groups = [];
        var currentGroup = null;

        rows.forEach(function(tr) {
            if (tr.classList.contains('customer-first-row')) {
                currentGroup = [tr];
                groups.push(currentGroup);
            } else if (currentGroup) {
                currentGroup.push(tr);
            }
        });

        groups.forEach(function(groupRows) {
            var count = groupRows.length;
            groupRows.forEach(function(tr) {
                var rowspanCells = tr.querySelectorAll('td[rowspan]');
                rowspanCells.forEach(function(cell) {
                    cell.setAttribute('rowspan', count);
                });
            });
        });
    }

    function saveOrder() {
        var order = [];
        var seen = new Set();

        document.querySelectorAll('#statsTableBody tr.product-row[data-product-id]').forEach(function(tr) {
            var id = tr.getAttribute('data-product-id');
            if (id && !seen.has(id)) {
                seen.add(id);
                order.push(parseInt(id));
            }
        });

        if (order.length < 2) return;

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
@endpush