@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📅 گزارش سالیانه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">گزارش سالیانه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- فیلتر سال --}}
        <form method="GET" action="{{ route('reports.annual') }}" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">سال</label>
                <select name="year" class="form-select">
                    @for($y = $currentYear - 5; $y <= $currentYear; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">فیلتر</button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('reports.annual.export', ['year' => $year]) }}" class="btn btn-success w-100">
                    <i class="fas fa-file-csv me-1"></i> خروجی CSV
                </a>
            </div>
        </form>

        {{-- ===== بخش تولید ===== --}}
        @if($productionReport->count())
            <h5 class="fw-bold mt-4 mb-3">📦 تولید سالیانه</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>نام محصول</th>
                            <th class="text-center">مجموع تولید</th>
                            <th class="text-center">زمان کارکرد (ساعت)</th>
                            <th class="text-center">تعویض قالب (ساعت)</th>
                            <th class="text-center">خرابی ماشین (ساعت)</th>
                            <th>پرس‌های استفاده‌شده</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productionReport as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ number_format($item->total_quantity) }}</td>
                                <td class="text-center">{{ number_format($item->total_time_hours, 2) }}</td>
                                <td class="text-center">{{ number_format($item->repair_hours, 2) }}</td>
                                <td class="text-center">{{ number_format($item->breakdown_hours, 2) }}</td>
                                <td>{{ $item->presses_text }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td>مجموع</td>
                            <td class="text-center">{{ number_format($productionReport->sum('total_quantity')) }}</td>
                            <td class="text-center">{{ number_format($productionReport->sum('total_time_hours'), 2) }}</td>
                            <td class="text-center">{{ number_format($productionReport->sum('repair_hours'), 2) }}</td>
                            <td class="text-center">{{ number_format($productionReport->sum('breakdown_hours'), 2) }}</td>
                            <td>—</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">هیچ داده‌ای برای تولید در سال انتخاب‌شده یافت نشد.</div>
        @endif

        {{-- ===== بخش پخت ===== --}}
        @if($firingReport->count())
            <h5 class="fw-bold mt-5 mb-3">🔥 پخت سالیانه</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle">نام محصول</th>
                            <th colspan="2" class="text-center">تونلی</th>
                            <th colspan="2" class="text-center">شاتل (مجموع)</th>
                            <th colspan="2" class="text-center">کوره ۱</th>
                            <th colspan="2" class="text-center">کوره ۲</th>
                            <th colspan="2" class="text-center">کوره ۳ (لعاب)</th>
                            <th colspan="2" class="text-center">کوره ۳ (موم)</th>
                            <th colspan="2" class="text-center">کوره ۴</th>
                            <th colspan="2" class="text-center">بسته‌بندی</th>
                        </tr>
                        <tr>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                            <th class="text-center">قطعات</th>
                            <th class="text-center">تعداد پخت</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($firingReport as $item)
                            <tr>
                                <td>{{ $item->product_name }}</td>
                                <td class="text-center">{{ number_format($item->tonneli_qty) }}</td>
                                <td class="text-center">{{ number_format($item->tonneli_count) }}</td>
                                <td class="text-center">{{ number_format($item->shuttle_total_qty) }}</td>
                                <td class="text-center">{{ number_format($item->shuttle_total_count) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_1_qty) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_1_count) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_2_qty) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_2_count) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_glaze_qty) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_glaze_count) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_mum_qty) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_3_mum_count) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_4_qty) }}</td>
                                <td class="text-center">{{ number_format($item->kiln_4_count) }}</td>
                                <td class="text-center">{{ number_format($item->packaging_qty) }}</td>
                                <td class="text-center">{{ number_format($item->packaging_count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary fw-bold">
                        <tr>
                            <td>مجموع</td>
                            <td class="text-center">{{ number_format($firingReport->sum('tonneli_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('tonneli_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('shuttle_total_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('shuttle_total_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_1_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_1_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_2_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_2_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_3_glaze_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_3_glaze_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_3_mum_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_3_mum_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_4_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('kiln_4_count')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('packaging_qty')) }}</td>
                            <td class="text-center">{{ number_format($firingReport->sum('packaging_count')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-info">هیچ داده‌ای برای پخت در سال انتخاب‌شده یافت نشد.</div>
        @endif
    </div>
</div>
@endsection