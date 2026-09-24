@extends('layouts.app')

@section('title', 'حسابداری - بدهکاران')

@push('styles')
<style>
    .debtor-row {
        border-right: 4px solid #dc3545;
    }
    .debtor-row.small-debt {
        border-right-color: #ffc107;
    }
    .debtor-row.positive {
        border-right-color: #198754;
    }
    .customer-name {
        font-size: 15px;
        font-weight: bold;
        color: #1e3a5f;
    }
    .stat-mini {
        font-size: 12px;
        color: #6c757d;
    }
    .stat-value {
        font-size: 14px;
        font-weight: bold;
    }
    .balance-box {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 16px;
    }
    .period-badge {
        background: #fff3cd;
        color: #664d03;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        border: 1px solid #ffecb5;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            گزارش بدهکاران
        </h4>
        <small class="text-muted">مشتریانی که مانده حساب دارند</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounting.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> بازگشت به پرداخت‌ها
        </a>
    </div>
</div>

{{-- فیلتر ماه/سال --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounting.debtors') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">سال</label>
                <select name="year" class="form-select form-select-sm">
                    @for($y = $currentJalali->getYear(); $y >= $currentJalali->getYear() - 5; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">ماه</label>
                <select name="month" class="form-select form-select-sm">
                    {{-- ✅ گزینه «همه ماه‌ها» --}}
                    <option value="all" {{ $currentMonth == 'all' ? 'selected' : '' }}>
                        📅 همه ماه‌ها
                    </option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                            {{ $monthNames[$m - 1] }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i> نمایش
                </button>
            </div>

            <div class="col-md-3 text-end">
                @if($isAllMonths)
                    <span class="period-badge">
                        <i class="fas fa-calendar-alt me-1"></i>
                        کل سال {{ $year }}
                    </span>
                @else
                    <span class="period-badge">
                        <i class="fas fa-calendar-day me-1"></i>
                        {{ $monthNames[$currentMonth - 1] }} {{ $year }}
                    </span>
                @endif
            </div>
        </form>
    </div>
</div>

@php
    $totalDebt = $debtorsData->sum('remaining');
    $totalSales = $debtorsData->sum('total_sales');
    $totalPaid = $debtorsData->sum('total_paid');
@endphp

{{-- آمار کلی --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="stat-mini">
                    مجموع فروش (تا پایان 
                    @if($isAllMonths)
                        سال
                    @else
                        {{ $monthNames[$currentMonth - 1] }}
                    @endif
                    )
                </div>
                <div class="stat-value text-primary mt-2" style="font-size: 18px;">
                    {{ number_format($totalSales) }} <small>ریال</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="stat-mini">مجموع پرداختی</div>
                <div class="stat-value text-success mt-2" style="font-size: 18px;">
                    {{ number_format($totalPaid) }} <small>ریال</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="stat-mini">مجموع بدهکاری</div>
                <div class="stat-value text-danger mt-2" style="font-size: 18px;">
                    {{ number_format($totalDebt) }} <small>ریال</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- لیست بدهکاران --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0">
            <i class="fas fa-list me-1"></i>
            بدهکاران ({{ $debtorsData->count() }} مشتری)
            @if($isAllMonths)
                — کل سال {{ $year }}
            @else
                — {{ $monthNames[$currentMonth - 1] }} {{ $year }}
            @endif
        </h6>
    </div>
    <div class="card-body p-0">
        @if($debtorsData->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                <h5>هیچ بدهکاری وجود ندارد</h5>
                <p>
                    @if($isAllMonths)
                        همه مشتریان تا پایان سال {{ $year }} تسویه کرده‌اند.
                    @else
                        همه مشتریان تا پایان {{ $monthNames[$currentMonth - 1] }} تسویه کرده‌اند.
                    @endif
                </p>
            </div>
        @else
            <div class="list-group list-group-flush">
                @foreach($debtorsData as $d)
                    @php
                        $rowClass = 'debtor-row';
                        if ($d->remaining < 1000000) $rowClass .= ' small-debt';
                    @endphp
                    <div class="list-group-item {{ $rowClass }}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div class="flex-grow-1">
                                <div class="customer-name">
                                    <i class="fas fa-user-circle me-1"></i>
                                    {{ $d->customer->name }}
                                </div>
                                @if($d->customer->phone)
                                    <div class="stat-mini mt-1">
                                        <i class="fas fa-phone me-1"></i>{{ $d->customer->phone }}
                                    </div>
                                @endif
                            </div>

                            <div class="balance-box text-center" style="min-width: 130px;">
                                <div class="stat-mini">مجموع فروش</div>
                                <div class="fw-bold text-primary">{{ number_format($d->total_sales) }}</div>
                            </div>

                            <div class="balance-box text-center" style="min-width: 130px;">
                                <div class="stat-mini">پرداخت شده</div>
                                <div class="fw-bold text-success">{{ number_format($d->total_paid) }}</div>
                            </div>

                            <div class="balance-box text-center bg-danger bg-opacity-10" style="min-width: 150px;">
                                <div class="stat-mini">مانده حساب (بدهی)</div>
                                <div class="fw-bold text-danger" style="font-size: 16px;">
                                    {{ number_format($d->remaining) }}
                                    <small class="text-muted">ریال</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection