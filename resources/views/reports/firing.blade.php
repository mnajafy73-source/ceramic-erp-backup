@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">🔥 گزارش پخت</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">گزارش پخت</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- فیلتر ماه و سال --}}
        <form method="GET" action="{{ route('reports.firing') }}" class="row g-3 mb-4">
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
                    @php
                        $monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                    @endphp
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ $monthNames[$m - 1] }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">فیلتر</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('reports.firing.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-success w-100">
                    <i class="fas fa-file-csv me-1"></i> خروجی CSV
                </a>
            </div>
        </form>

        {{-- جدول گزارش پخت --}}
        @if($reportData->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>نام محصول</th>
                            <th class="text-center">تونلی</th>
                            <th class="text-center">شاتل (مجموع)</th>
                            <th class="text-center">کوره ۱</th>
                            <th class="text-center">کوره ۲</th>
                            <th class="text-center">کوره ۳ (لعاب)</th>
                            <th class="text-center">کوره ۳ (موم)</th>
                            <th class="text-center">کوره ۴</th>
                            <th class="text-center">بسته‌بندی</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ number_format($item->tonneli) }}</td>
                                <td class="text-center">{{ number_format($item->shuttle_total) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_1) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_2) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_glaze) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_mum) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_4) }}</td>
                                <td class="text-center">{{ number_format($item->packaging) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td>مجموع</td>
                            <td class="text-center">{{ number_format($reportData->sum('tonneli')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('shuttle_total')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('kiln_1')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('kiln_2')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('kiln_3_glaze')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('kiln_3_mum')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('kiln_4')) }}</td>
                            <td class="text-center">{{ number_format($reportData->sum('packaging')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">هیچ داده‌ای برای ماه و سال انتخاب‌شده یافت نشد.</div>
        @endif
    </div>
</div>
@endsection