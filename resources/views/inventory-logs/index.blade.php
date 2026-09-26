@extends('layouts.app')

@section('title', 'آخرین تغییرات موجودی')

@push('styles')
<style>
    .log-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px 16px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
    }
    .log-filters .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-filters .btn .badge-count {
        background: rgba(0,0,0,0.15);
        color: inherit;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: bold;
    }
    .log-filters .btn.active .badge-count {
        background: rgba(255,255,255,0.3);
    }

    .log-row {
        border-right: 4px solid #dee2e6;
        transition: background 0.15s;
    }
    .log-row:hover { background: #f8f9fa; }
    .log-row.positive { border-right-color: #0a8754; }
    .log-row.negative { border-right-color: #c1121f; }
    .log-row.neutral  { border-right-color: #6c757d; }
    .log-row.event    { border-right-color: #0d6efd; background: #f8f9ff; }

    .log-subject-name {
        font-size: 15px;
        font-weight: bold;
        color: #1e3a5f;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-subject-name i { color: #0d6efd; font-size: 14px; }

    .log-source {
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .log-delta {
        font-weight: 900;
        font-size: 17px;
        font-family: 'Courier New', monospace;
    }
    .log-delta.text-success { color: #0a8754 !important; }
    .log-delta.text-danger  { color: #c1121f !important; }
    .log-description { font-size: 12px; color: #6c757d; margin-top: 6px; }
    .log-time { font-size: 12px; color: #6c757d; }

    .empty-state { text-align: center; padding: 60px 20px; color: #adb5bd; }
    .empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.3; }

    .quick-dates { display: flex; gap: 6px; flex-wrap: wrap; }
    .quick-dates .btn { border-radius: 20px; font-weight: 600; font-size: 12px; padding: 5px 12px; }

    .datepicker-plot-area { font-family: Tahoma, sans-serif !important; }

    .select2-container--bootstrap-5 .select2-selection { font-size: 14px; }

    /* ═══════════════════════════════════════════════════════════
       استایل جزئیات رویداد
       ═══════════════════════════════════════════════════════════ */
    .log-details-box {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 6px 10px;
        margin-top: 6px;
        font-size: 13px;
        line-height: 1.4;
        font-family: Tahoma, sans-serif;
        color: #343a40;
    }
    .log-details-title {
        font-weight: bold;
        color: #1e3a5f;
        font-size: 13px;
        margin: 0 0 2px 0;
        padding: 0;
    }
    .log-details-line {
        margin: 0;
        padding: 0;
        line-height: 1.5;
    }
    .log-details-line.heading {
        font-weight: bold;
        color: #495057;
        margin-top: 4px;
    }

    /* ✅ رنگ‌های پررنگ برای تغییرات مواد */
    .material-change {
        padding: 3px 10px;
        border-radius: 4px;
        margin: 2px 0;
        font-weight: 700;
        border-right: 4px solid;
    }
    .material-change.increase {
        border-right-color: #0a8754;
        color: #065f3b;
        background: #d1f4e0;
    }
    .material-change.decrease {
        border-right-color: #c1121f;
        color: #8a0a14;
        background: #fbdde0;
    }
    .material-change .mat-name {
        font-weight: 800;
        color: #212529;
        margin-left: 4px;
    }

    /* ✅ کلید حل مشکل bidi */
    .ltr-num {
        display: inline-block;
        direction: ltr;
        unicode-bidi: isolate;
        font-family: 'Courier New', monospace;
        font-weight: 900;
        font-size: 13.5px;
    }
    .material-change.increase .ltr-num { color: #0a8754; }
    .material-change.decrease .ltr-num { color: #c1121f; }

    .event-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #cfe2ff;
        color: #084298;
        font-size: 11px;
        font-weight: bold;
        padding: 3px 8px;
        border-radius: 6px;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="fas fa-history me-2 text-primary"></i>
            آخرین تغییرات موجودی
        </h4>
        <small class="text-muted">تمام تغییرات موجودی‌ها (دستی، ایمپورت، تولید، فروش و ...)</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteOldModal">
            <i class="fas fa-trash me-1"></i> پاک کردن لاگ‌های قدیمی
        </button>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> بازگشت به موجودی
        </a>
    </div>
</div>

{{-- فیلتر نوع موجودی --}}
<div class="log-filters">
    <a href="{{ route('inventory-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'all'])) }}"
       class="btn {{ request('type', 'all') === 'all' ? 'btn-dark active' : 'btn-outline-dark' }}">
        <i class="fas fa-list"></i>
        همه
        <span class="badge-count">{{ number_format($typeCounts['all']) }}</span>
    </a>

    @php
        $typeButtons = [
            'warehouse'    => ['label' => 'موجودی انبار',     'icon' => 'fa-warehouse',      'color' => 'primary'],
            'raw'          => ['label' => 'موجودی خام',       'icon' => 'fa-cube',           'color' => 'warning'],
            'wax'          => ['label' => 'موم',              'icon' => 'fa-fire',           'color' => 'danger'],
            'glaze1300'    => ['label' => '۱۳۰۰ درجه',        'icon' => 'fa-fire-alt',       'color' => 'dark'],
            'shoulder'     => ['label' => 'شانه شده',         'icon' => 'fa-bars',           'color' => 'info'],
            'waste_mum'    => ['label' => 'ضایعات موم',       'icon' => 'fa-trash',          'color' => 'secondary'],
            'raw_material' => ['label' => 'مواد اولیه',       'icon' => 'fa-flask',          'color' => 'success'],
            'packaging'    => ['label' => 'کارتن و لایه',     'icon' => 'fa-box',            'color' => 'info'],
        ];
    @endphp

    @foreach($typeButtons as $key => $cfg)
        <a href="{{ route('inventory-logs.index', array_merge(request()->except('type', 'page', 'quick', 'product_id'), ['type' => $key])) }}"
           class="btn {{ request('type') === $key ? 'btn-' . $cfg['color'] . ' active' : 'btn-outline-' . $cfg['color'] }}">
            <i class="fas {{ $cfg['icon'] }}"></i>
            {{ $cfg['label'] }}
            <span class="badge-count">{{ number_format($typeCounts[$key]) }}</span>
        </a>
    @endforeach
</div>

{{-- دکمه‌های سریع تاریخ --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-2">
                <span class="fw-bold small text-muted">
                    <i class="fas fa-bolt text-warning me-1"></i>
                    میانبر تاریخ:
                </span>
            </div>
            <div class="col-md-10">
                <div class="quick-dates">
                    <a href="{{ route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'today'])) }}"
                       class="btn {{ $currentQuick === 'today' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="fas fa-calendar-day"></i> امروز
                    </a>
                    <a href="{{ route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'yesterday'])) }}"
                       class="btn {{ $currentQuick === 'yesterday' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="fas fa-calendar-minus"></i> دیروز
                    </a>
                    <a href="{{ route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'this_week'])) }}"
                       class="btn {{ $currentQuick === 'this_week' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="fas fa-calendar-week"></i> این هفته
                    </a>
                    <a href="{{ route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'this_month'])) }}"
                       class="btn {{ $currentQuick === 'this_month' ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="fas fa-calendar-alt"></i> این ماه
                    </a>
                    <a href="{{ route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'))) }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> حذف فیلتر تاریخ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- فیلترهای بیشتر --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form action="{{ route('inventory-logs.index') }}" method="GET" class="row g-3 align-items-end">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif

            <div class="col-md-3">
                <label class="form-label small fw-bold">از تاریخ</label>
                <input type="text" name="date_from" id="date_from"
                       class="form-control form-control-sm jalali-date-input"
                       value="{{ $currentDateFrom }}" placeholder="مثال: 1405/06/01" autocomplete="off">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">تا تاریخ</label>
                <input type="text" name="date_to" id="date_to"
                       class="form-control form-control-sm jalali-date-input"
                       value="{{ $currentDateTo }}" placeholder="مثال: 1405/06/30" autocomplete="off">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">منبع</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="all" {{ request('source', 'all') === 'all' ? 'selected' : '' }}>همه</option>
                    <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>دستی</option>
                    <option value="import" {{ request('source') === 'import' ? 'selected' : '' }}>ایمپورت اکسل</option>
                    <option value="production" {{ request('source') === 'production' ? 'selected' : '' }}>تولید</option>
                    <option value="tonneli" {{ request('source') === 'tonneli' ? 'selected' : '' }}>کوره تونلی</option>
                    <option value="shuttle" {{ request('source') === 'shuttle' ? 'selected' : '' }}>کوره شاتل</option>
                    <option value="sale" {{ request('source') === 'sale' ? 'selected' : '' }}>فروش</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">کاربر</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">همه کاربران</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($products->count() > 0)
                <div class="col-md-4">
                    <label class="form-label small fw-bold">انتخاب محصول</label>
                    <select name="product_id" id="product_id" class="form-select form-select-sm product-search-select">
                        <option value="">همه محصولات</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-5">
                <label class="form-label small fw-bold">جستجو</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="نام محصول، ماده، بسته، توضیحات..." value="{{ request('search') }}">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="fas fa-search me-1"></i> اعمال فیلتر
                </button>
                <a href="{{ route('inventory-logs.index', ['type' => request('type', 'all')]) }}"
                   class="btn btn-secondary btn-sm flex-fill">
                    <i class="fas fa-times me-1"></i> حذف همه
                </a>
            </div>
        </form>
    </div>
</div>

{{-- لیست لاگ‌ها --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        @if($logs->isEmpty())
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h5>هیچ تغییری یافت نشد</h5>
                <p>فیلترها رو تغییر بده یا صبر کن تا تغییرات جدید ثبت بشن.</p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($logs as $log)
                    @php
                        $isEvent = $log->has_details;
                        $delta = $log->delta;
                        if ($isEvent) {
                            $deltaClass = 'event';
                        } else {
                            $deltaClass = $delta > 0 ? 'positive' : ($delta < 0 ? 'negative' : 'neutral');
                        }
                    @endphp
                    <div class="list-group-item log-row {{ $deltaClass }}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div class="flex-grow-1 w-100">

                                @if($isEvent)
                                    <div class="mb-2">
                                        <span class="log-subject-name">
                                            <i class="fas fa-layer-group"></i>
                                            {{ $log->subject_name ?? '—' }}
                                        </span>
                                        <span class="event-badge ms-2">
                                            <i class="fas fa-bolt"></i>
                                            رویداد گروهی
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                        <span class="badge {{ $log->inventory_type_badge }}">
                                            {{ $log->inventory_type_label }}
                                        </span>
                                        @if($log->source_label)
                                            <span class="log-source text-primary">
                                                <i class="fas fa-arrow-left"></i>
                                                {{ $log->source_label }}
                                            </span>
                                        @endif
                                        @if($log->user)
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $log->user->name }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- ✅ نمایش جزئیات کامل --}}
                                    @php
                                        $lines = explode("\n", $log->details);
                                    @endphp
                                    <div class="log-details-box">
                                        @foreach($lines as $line)
                                            @php $trimmed = trim($line); @endphp
                                            @if($trimmed === '')
                                                {{-- خط خالی --}}
                                            @elseif(str_starts_with($trimmed, 'CHANGE|') || str_starts_with($trimmed, 'CHANGE_RAW|') || str_starts_with($trimmed, 'CHANGE_PKG|'))
                                                @php
                                                    $parts = explode('|', $trimmed);
                                                    $type = $parts[0] ?? 'CHANGE';
                                                    $matName = $parts[1] ?? '';
                                                    $oldVal  = (int) ($parts[2] ?? 0);
                                                    $newVal  = (int) ($parts[3] ?? 0);
                                                    $deltaVal = (int) ($parts[4] ?? 0);
                                                    $sign = $deltaVal > 0 ? '+' : '';
                                                    $isIncrease = $deltaVal > 0;
                                                    $unit = ($type === 'CHANGE') ? 'گرم' : 'عدد';
                                                @endphp
                                                <div class="log-details-line material-change {{ $isIncrease ? 'increase' : 'decrease' }}">
                                                    <span class="mat-name">• {{ $matName }}:</span>
                                                    <span class="ltr-num">{{ number_format($oldVal) }} → {{ number_format($newVal) }} {{ $unit }}</span>
                                                    <span class="ltr-num">({{ $sign }}{{ number_format($deltaVal) }} {{ $unit }})</span>
                                                </div>
                                            @elseif(str_starts_with($trimmed, '📋'))
                                                <div class="log-details-title">{{ $trimmed }}</div>
                                            @elseif(str_starts_with($trimmed, '🔹'))
                                                <div class="log-details-line">{{ $trimmed }}</div>
                                            @elseif(str_starts_with($trimmed, '📦'))
                                                <div class="log-details-line heading">{{ $trimmed }}</div>
                                            @elseif(str_starts_with($trimmed, '📌'))
                                                <div class="log-details-line heading">{{ $trimmed }}</div>
                                            @elseif(str_contains($trimmed, '•'))
                                                @php
                                                    $isIncrease = str_contains($trimmed, '+');
                                                    $clean = preg_replace('/[\x{202A}-\x{202E}]/u', '', $trimmed);
                                                @endphp
                                                <div class="log-details-line material-change {{ $isIncrease ? 'increase' : 'decrease' }}">
                                                    <span class="ltr-num">{{ $clean }}</span>
                                                </div>
                                            @else
                                                <div class="log-details-line">{{ $trimmed }}</div>
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="log-time mt-2">
                                        <i class="far fa-clock me-1"></i>
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i:s') }}
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <span class="log-subject-name">
                                            <i class="fas fa-box-open"></i>
                                            {{ $log->subject_name ?? '—' }}
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                        <span class="badge {{ $log->inventory_type_badge }}">
                                            {{ $log->inventory_type_label }}
                                        </span>
                                        @if($log->source_label)
                                            <span class="log-source text-primary">
                                                <i class="fas fa-arrow-left"></i>
                                                {{ $log->source_label }}
                                            </span>
                                        @endif
                                        @if($log->user)
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-user me-1"></i>
                                                {{ $log->user->name }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($log->description)
                                        <div class="log-description">
                                            <i class="fas fa-info-circle me-1"></i>
                                            {{ $log->description }}
                                        </div>
                                    @endif

                                    <div class="log-time mt-2">
                                        <i class="far fa-clock me-1"></i>
                                        {{ \Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i:s') }}
                                    </div>
                                @endif
                            </div>

                            @if(!$isEvent)
                                <div class="text-start" style="min-width: 240px;">
                                    <div class="d-flex gap-3 justify-content-end align-items-center">
                                        <div class="text-center">
                                            <div class="small text-muted">قبل</div>
                                            <div class="fw-bold">{{ number_format($log->old_value) }}</div>
                                        </div>
                                        <i class="fas fa-arrow-left text-muted"></i>
                                        <div class="text-center">
                                            <div class="small text-muted">بعد</div>
                                            <div class="fw-bold">{{ number_format($log->new_value) }}</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="small text-muted">تغییر</div>
                                            <div class="log-delta text-{{ $log->delta_color }}">
                                                {{ $log->delta_label }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="mt-3">
    {{ $logs->links() }}
</div>

{{-- Modal پاک کردن لاگ‌های قدیمی --}}
<div class="modal fade" id="deleteOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('inventory-logs.delete-old') }}" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>
                        پاک کردن لاگ‌های قدیمی
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>هشدار:</strong> این عمل قابل بازگشت نیست!
                    </div>

                    <label class="form-label fw-bold">لاگ‌های قدیمی‌تر از:</label>
                    <select name="period" class="form-select" required>
                        <option value="1_month">۱ ماه پیش</option>
                        <option value="3_months">۳ ماه پیش</option>
                        <option value="6_months" selected>۶ ماه پیش</option>
                        <option value="1_year">۱ سال پیش</option>
                        <option value="all" class="text-danger">همه لاگ‌ها (پاک کردن کامل)</option>
                    </select>

                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        لاگ‌های جدیدتر از بازه انتخابی حفظ می‌شوند.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('مطمئن هستید؟ این عمل قابل بازگشت نیست.');">
                        <i class="fas fa-trash me-1"></i> پاک کن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
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

        if ($.fn.select2 && $('#product_id').length) {
            $('#product_id').select2({
                placeholder: 'جستجو و انتخاب محصول...',
                allowClear: true,
                width: '100%',
                dir: 'rtl',
                language: {
                    searching: function() { return 'در حال جستجو...'; },
                    noResults: function() { return 'محصولی یافت نشد'; }
                }
            });
        }
    });
</script>
@endpush