@extends('layouts.app')

@section('title', 'حسابداری - پرداخت‌ها')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .stat-card {
        background: #fff;
        border-radius: 12px;
        padding: 18px 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
        height: 100%;
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 20px;
    }
    .stat-card .stat-label {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 4px;
    }
    .stat-card .stat-value {
        font-size: 20px;
        font-weight: bold;
        color: #1e3a5f;
    }

    .payments-table {
        font-size: 13px;
    }
    .payments-table td {
        font-size: 13px;
        vertical-align: middle;
    }

    .payments-table thead th {
        position: sticky;
        top: 70px;
        z-index: 100;
        background: #212529 !important;
        color: #fff !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        border-bottom: 2px solid #000;
    }

    .payments-table tfoot td {
        position: sticky;
        bottom: 0;
        background: #e9ecef !important;
        z-index: 100;
        box-shadow: 0 -2px 6px rgba(0,0,0,0.15);
        border-top: 2px solid #adb5bd;
    }

    .quick-dates {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .quick-dates .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        padding: 4px 12px;
    }
    .datepicker-plot-area {
        font-family: Tahoma, sans-serif !important;
    }

    /* ✅ اینپوت مبلغ */
    .amount-input {
        text-align: left;
        direction: ltr;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 15px;
        letter-spacing: 1px;
        color: #0a3622;
        background: #f8f9fa;
    }
    .amount-input:focus {
        background: #fff;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="fas fa-calculator me-2 text-success"></i>
            حسابداری - پرداخت‌های مشتریان
        </h4>
        <small class="text-muted">مدیریت پرداخت‌ها و مشاهده بدهکاران</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounting.debtors') }}" class="btn btn-warning btn-sm">
            <i class="fas fa-exclamation-triangle me-1"></i> گزارش بدهکاران
        </a>
        <form action="{{ route('accounting.import') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"
                    onclick="return confirm('آیا از ایمپورت پرداخت‌ها از اکسل مطمئن هستید؟\n\nشیت: حسابداری')">
                <i class="fas fa-upload me-1"></i> ایمپورت از اکسل
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- آمار --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <div class="stat-label">مجموع پرداخت‌ها</div>
                    <div class="stat-value">{{ number_format($totalAmount) }} <small class="text-muted fw-normal">ریال</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <div class="stat-label">تعداد پرداخت‌ها</div>
                    <div class="stat-value">{{ number_format($paymentsCount) }} <small class="text-muted fw-normal">فقره</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: linear-gradient(135deg, #6f42c1, #d63384);">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <div class="stat-label">تعداد مشتریان</div>
                    <div class="stat-value">{{ number_format($customersCount) }} <small class="text-muted fw-normal">نفر</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- فرم افزودن دستی --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-plus-circle text-success me-1"></i>
            افزودن پرداخت جدید
        </h6>
    </div>
    <div class="card-body">
        <form action="{{ route('accounting.store') }}" method="POST" class="row g-3" id="paymentForm">
            @csrf

            <div class="col-md-3">
                <label class="form-label small fw-bold">مشتری <span class="text-danger">*</span></label>
                <select name="customer_id" id="customer_select" class="form-select form-select-sm customer-select" style="width:100%;">
                    <option value="">— انتخاب مشتری —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">یا نام مشتری جدید بنویس:</small>
                <input type="text" name="customer_name" class="form-control form-control-sm mt-1"
                       placeholder="نام مشتری جدید..." value="{{ old('customer_name') }}">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">مبلغ (ریال) <span class="text-danger">*</span></label>
                {{-- ✅ مهم: type="text" (نه number) تا کاما قبول کنه --}}
                <input type="text"
                       name="amount"
                       id="amount_input"
                       class="form-control form-control-sm amount-input"
                       value="{{ old('amount') }}"
                       placeholder="مثلاً 1000000"
                       autocomplete="off"
                       required>
                <small class="text-muted" id="amount_hint" style="font-size: 11px;"></small>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">تاریخ <span class="text-danger">*</span></label>
                <input type="text" name="date" id="date_input"
                       class="form-control form-control-sm jalali-date-input"
                       value="{{ old('date', \Morilog\Jalali\Jalalian::now()->format('Y/m/d')) }}"
                       autocomplete="off" required>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">روش پرداخت</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">—</option>
                    <option value="نقد">نقد</option>
                    <option value="کارت به کارت">کارت به کارت</option>
                    <option value="چک">چک</option>
                    <option value="حواله">حواله</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">توضیحات</label>
                <input type="text" name="description" class="form-control form-control-sm"
                       value="{{ old('description') }}">
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="fas fa-save"></i>
                </button>
            </div>
        </form>

        @if($errors->any())
            <div class="alert alert-danger mt-3 mb-0">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

{{-- فیلترها --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('accounting.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">نام مشتری</label>
                <input type="text" name="customer_name" class="form-control form-control-sm"
                       value="{{ request('customer_name') }}" placeholder="جستجو...">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">از تاریخ</label>
                <input type="text" name="date_from" class="form-control form-control-sm jalali-date-input"
                       value="{{ request('date_from') }}" autocomplete="off" placeholder="1405/06/01">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">تا تاریخ</label>
                <input type="text" name="date_to" class="form-control form-control-sm jalali-date-input"
                       value="{{ request('date_to') }}" autocomplete="off" placeholder="1405/06/30">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">سال</label>
                <select name="year" class="form-select form-select-sm">
                    <option value="">همه</option>
                    @for($y = $currentYear; $y >= $currentYear - 5; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">ماه</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="">همه</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('accounting.index') }}" class="btn btn-secondary btn-sm w-100">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

{{-- جدول پرداخت‌ها --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-list me-1"></i> لیست پرداخت‌ها ({{ $payments->total() }} مورد)</h6>
        @if($payments->total() > 0)
            <form action="{{ route('accounting.clear-all') }}" method="POST" class="d-inline"
                  onsubmit="return confirm('⚠️ مطمئن هستید؟ پرداخت‌های ایمپورتی پاک می‌شوند (دستی‌ها حفظ می‌شوند).')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-trash me-1"></i> پاک کردن ایمپورتی‌ها
                </button>
            </form>
        @endif
    </div>
    <div class="card-body p-0">
        @if($payments->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                <p>هیچ پرداختی ثبت نشده است</p>
            </div>
        @else
            <table class="table table-hover payments-table mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th class="text-end">مبلغ</th>
                        <th class="text-center">روش</th>
                        <th class="text-center">منبع</th>
                        <th>توضیحات</th>
                        <th style="width:80px;" class="text-center">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $i => $p)
                        <tr>
                            <td class="text-muted">{{ $payments->firstItem() + $i }}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $p->jalali_date ?? '—' }}
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-user-circle text-secondary me-1"></i>
                                {{ $p->user_name }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                {{ number_format($p->amount) }}
                                <small class="text-muted">ریال</small>
                            </td>
                            <td class="text-center">
                                @if($p->payment_method)
                                    <span class="badge bg-info">{{ $p->payment_method }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($p->is_imported)
                                    <span class="badge bg-primary">اکسل</span>
                                @else
                                    <span class="badge bg-success">دستی</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $p->description ?? '—' }}</td>
                            <td class="text-center">
                                <form action="{{ route('accounting.destroy', $p) }}" method="POST"
                                      onsubmit="return confirm('حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="3" class="text-center">مجموع کل</td>
                        <td class="text-end">{{ number_format($totalAmount) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>
</div>

<div class="mt-3">{{ $payments->links() }}</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
//  ✅ فرمت سه رقم سه رقم مبلغ موقع تایپ — با JavaScript خالص
// ═══════════════════════════════════════════════════════════════
(function() {
    'use strict';

    // صبر کن تا DOM آماده بشه
    function init() {
        var input = document.getElementById('amount_input');
        var hint = document.getElementById('amount_hint');

        if (!input) {
            console.warn('[amount] input not found');
            return;
        }

        console.log('[amount] formatter initialized');

        // تبدیل اعداد فارسی/عربی به انگلیسی
        function toEnglishDigits(str) {
            return String(str)
                .replace(/[۰-۹]/g, function(d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })
                .replace(/[٠-٩]/g, function(d) { return '٠١٢٣٤٥٦٧٨٩'.indexOf(d); });
        }

        // فرمت‌دهی: حذف کاراکترهای غیر عددی + کاما
        function formatNumber(val) {
            var cleaned = toEnglishDigits(val).replace(/[^\d]/g, '');
            if (cleaned === '') return '';
            return cleaned.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // آپدیت hint (معادل فارسی)
        function updateHint() {
            if (!hint) return;
            var raw = toEnglishDigits(input.value).replace(/[^\d]/g, '');
            if (raw === '') {
                hint.textContent = '';
                return;
            }
            var num = parseInt(raw, 10);
            if (isNaN(num)) {
                hint.textContent = '';
                return;
            }
            try {
                hint.textContent = num.toLocaleString('fa-IR') + ' ریال';
            } catch (e) {
                hint.textContent = raw + ' ریال';
            }
        }

        // ✅ رویداد input - بهترین راه برای گرفتن همه تغییرات (keyboard, paste, drag)
        input.addEventListener('input', function(e) {
            var cursorPos = this.selectionStart;
            var oldValue = this.value;
            var oldLen = oldValue.length;

            // فقط ارقام قبل از cursor رو بشمار
            var digitsBeforeCursor = toEnglishDigits(oldValue.substring(0, cursorPos))
                .replace(/[^\d]/g, '').length;

            // فرمت کن
            var formatted = formatNumber(oldValue);
            this.value = formatted;

            // جای cursor رو درست پیدا کن
            var newPos = 0;
            var digitCount = 0;
            for (var i = 0; i < formatted.length; i++) {
                if (/\d/.test(formatted[i])) {
                    digitCount++;
                    if (digitCount === digitsBeforeCursor) {
                        newPos = i + 1;
                        break;
                    }
                }
            }
            if (digitsBeforeCursor === 0) newPos = 0;
            if (newPos === 0 && formatted.length > 0 && digitsBeforeCursor > 0) {
                newPos = formatted.length;
            }

            try {
                this.setSelectionRange(newPos, newPos);
            } catch (err) { /* ignore */ }

            updateHint();
        });

        // ✅ رویداد keyup به عنوان fallback
        input.addEventListener('keyup', function() {
            updateHint();
        });

        // ✅ مقدار اولیه
        if (input.value) {
            input.value = formatNumber(input.value);
        }
        updateHint();

        // ✅ قبل از submit، کاماها رو حذف کن
        var form = document.getElementById('paymentForm');
        if (form) {
            form.addEventListener('submit', function() {
                var raw = toEnglishDigits(input.value).replace(/[^\d]/g, '');
                input.value = raw;
            });
        }
    }

    // اجرا
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

<script>
// ═══════════════════════════════════════════════════════════════
//  Select2 و Date picker
// ═══════════════════════════════════════════════════════════════
(function() {
    function init() {
        // اگه jQuery نیست، کاری نکن
        if (typeof window.jQuery === 'undefined') {
            console.warn('[select2/datepicker] jQuery not loaded');
            return;
        }

        var $ = window.jQuery;

        // Select2
        if ($.fn.select2) {
            $('.customer-select').select2({
                placeholder: 'جستجو و انتخاب مشتری...',
                allowClear: true,
                width: '100%',
                dir: 'rtl'
            });
        }

        // Date picker
        if (typeof $.fn.pDatepicker !== 'undefined') {
            $('.jalali-date-input').pDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                autoClose: true,
                persianDigit: false,
                observer: true,
                calendar: {
                    persian: { locale: 'fa', leapYearMode: 'algorithmic' }
                },
                toolbox: { calendarSwitch: { enabled: false } },
                navigator: { scroll: { enabled: true } },
                timePicker: { enabled: false }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush