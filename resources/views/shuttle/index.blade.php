@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">پخت‌های شاتل</h4>
    <a href="{{ route('shuttle.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

{{-- فیلتر سال، ماه و کوره --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form action="{{ route('shuttle.index') }}" method="GET" id="filter-form" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">سال</label>
                <select name="year" class="form-select form-select-sm" id="year-select">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $year == $defaultYear ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">ماه</label>
                <select name="month" class="form-select form-select-sm" id="month-select">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $m == $defaultMonth ? 'selected' : '' }}>ماه {{ $m }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">کوره</label>
                <select name="kiln_type" class="form-select form-select-sm" id="kiln-select">
                    <option value="">همه</option>
                    <option value="kiln_1" {{ old('kiln_type', $defaultKiln) == 'kiln_1' ? 'selected' : '' }}>کوره ۱</option>
                    <option value="kiln_2" {{ old('kiln_type', $defaultKiln) == 'kiln_2' ? 'selected' : '' }}>کوره ۲</option>
                    <option value="kiln_3" {{ old('kiln_type', $defaultKiln) == 'kiln_3' ? 'selected' : '' }}>کوره ۳</option>
                    <option value="packaging" {{ old('kiln_type', $defaultKiln) == 'packaging' ? 'selected' : '' }}>بسته‌بندی</option>
                </select>
            </div>
            <div class="col-md-3">
                {{-- فضای خالی --}}
            </div>
        </form>
    </div>
</div>

{{-- جدول خلاصه --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <h6 class="fw-bold mb-2 small">خلاصه پخت‌ها در ماه {{ $defaultMonth }} سال {{ $defaultYear }}</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="small">نوع کوره</th>
                        <th class="small">تعداد پخت (شماره‌های متمایز)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryData as $key => $data)
                        <tr>
                            <td class="small">{{ $data['label'] }}</td>
                            <td class="small">{{ $data['count'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="table-primary">
                        <td class="small"><strong>مجموع</strong></td>
                        <td class="small"><strong>{{ array_sum(array_column($summaryData, 'count')) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- پیام موفقیت --}}
@if(session('success'))
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>{{ session('success') }}</span>
        @if(session('undo_record'))
            <a href="{{ route('undo.restore') }}" class="btn btn-sm btn-warning">برگرداندن</a>
        @endif
    </div>
@endif

{{-- لیست پخت‌ها --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>نوع کوره</th>
                        <th>شماره پخت</th>
                        <th>تعداد محصولات</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $kilnLabels = ['kiln_1' => 'کوره ۱', 'kiln_2' => 'کوره ۲', 'kiln_3' => 'کوره ۳', 'packaging' => 'بسته‌بندی'];
                    @endphp
                    @forelse($batches as $batch)
                    <tr>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($batch->date)->format('Y/m/d') }}</td>
                        <td>{{ $kilnLabels[$batch->kiln_type] ?? $batch->kiln_type }}</td>
                        <td><span class="badge bg-primary">{{ $batch->firing_number }}</span></td>
                        <td>{{ \App\Models\ShuttleFiring::where('firing_number', $batch->firing_number)->whereDate('date', $batch->date)->where('kiln_type', $batch->kiln_type)->count() }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('shuttle.show', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type]) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('shuttle.edit', ['firingNumber' => $batch->firing_number, 'date' => $batch->date->format('Y-m-d'), 'kiln_type' => $batch->kiln_type]) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>

                            <form action="{{ route('shuttle.destroy-batch') }}" method="POST" onsubmit="return confirm('مطمئن هستید کل این پخت حذف شود؟')">
                                @csrf
                                <input type="hidden" name="firing_number" value="{{ $batch->firing_number }}">
                                <input type="hidden" name="date" value="{{ $batch->date->format('Y-m-d') }}">
                                <input type="hidden" name="kiln_type" value="{{ $batch->kiln_type }}">
                                <button class="btn btn-sm btn-outline-danger" title="حذف کل پخت"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center">هیچ پختی در این ماه یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $batches->links() }}</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const yearSelect = document.getElementById('year-select');
        const monthSelect = document.getElementById('month-select');
        const kilnSelect = document.getElementById('kiln-select');
        const filterForm = document.getElementById('filter-form');

        function submitForm() {
            filterForm.submit();
        }

        yearSelect.addEventListener('change', submitForm);
        monthSelect.addEventListener('change', submitForm);
        kilnSelect.addEventListener('change', submitForm);
    });
</script>
@endpush