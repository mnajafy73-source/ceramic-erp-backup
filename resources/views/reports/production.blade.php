@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📊 گزارش تولید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">گزارش تولید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- فیلتر ماه و سال --}}
        <form method="GET" action="{{ route('reports.production') }}" class="row g-3 mb-4">
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
                <a href="{{ route('reports.production.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-success w-100">
                    <i class="fas fa-file-csv me-1"></i> خروجی CSV
                </a>
            </div>
        </form>

        {{-- خلاصه تجمیعی هر محصول --}}
        @if($summaryByProduct->count())
            <h5 class="fw-bold mt-4 mb-3">خلاصه تجمیعی هر محصول</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>نام محصول</th>
                            <th>مجموع تولید</th>
                            <th>مجموع زمان کارکرد (ساعت)</th>
                            <th>مجموع تعویض قالب (ساعت)</th>
                            <th>مجموع خرابی ماشین (ساعت)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryByProduct as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ number_format($item->total_quantity) }}</td>
                                <td>{{ number_format($item->total_time_hours, 2) }}</td>
                                <td>{{ number_format($item->repair_hours, 2) }}</td>
                                <td>{{ number_format($item->breakdown_hours, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- جزئیات بر اساس محصول و پرس --}}
        @if($reportData->count())
            <h5 class="fw-bold mt-4 mb-3">جزئیات بر اساس محصول و پرس</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>نام محصول</th>
                            <th>نام پرس</th>
                            <th>تعداد تولید</th>
                            <th>زمان کارکرد (ساعت)</th>
                            <th>تعویض قالب (ساعت)</th>
                            <th>خرابی ماشین (ساعت)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td>{{ $item->press_name }}</td>
                                <td>{{ number_format($item->total_quantity) }}</td>
                                <td>{{ number_format($item->total_time_hours, 2) }}</td>
                                <td>{{ number_format($item->repair_hours, 2) }}</td>
                                <td>{{ number_format($item->breakdown_hours, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">هیچ داده‌ای برای ماه و سال انتخاب‌شده یافت نشد.</div>
        @endif
    </div>
</div>
@endsection