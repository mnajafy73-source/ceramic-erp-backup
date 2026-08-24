@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">گزارش تولید ماهانه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">گزارش تولید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <!-- فرم فیلتر -->
        <form method="GET" action="{{ route('reports.production') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    @for ($y = 1400; $y <= $currentYear; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select">
                    @foreach (['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'] as $i => $monthName)
                        <option value="{{ $i+1 }}" {{ ($i+1) == $month ? 'selected' : '' }}>{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">نمایش</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('reports.production.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-success w-100">دانلود CSV</a>
            </div>
        </form>

        <!-- جدول داده‌ها -->
        @if($reportData->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>نام قطعه</th>
                            <th>دستگاه</th>
                            <th>عملیات</th>
                            <th>حفره</th>
                            <th>تعداد</th>
                            <th>تعویض قالب (ساعت)</th>
                            <th>خرابی ماشین (ساعت)</th>
                            <th>تاریخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['product_name'] }}</td>
                                <td>{{ $row['press_name'] }}</td>
                                <td>{{ $row['stage'] }}</td>
                                <td>{{ $row['cavities'] }}</td>
                                <td>{{ number_format($row['quantity']) }}</td>
                                <td>{{ number_format($row['repair_hours'], 2) }}</td>
                                <td>{{ number_format($row['breakdown_hours'], 2) }}</td>
                                <td>{{ $row['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">هیچ داده‌ای برای این ماه یافت نشد.</div>
        @endif
    </div>
</div>
@endsection