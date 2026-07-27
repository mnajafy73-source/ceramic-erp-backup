@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">پخت‌های شاتل</h4>
    <a href="{{ route('shuttle.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

{{-- نمایش پیام موفقیت با دکمه برگرداندن --}}
@if(session('success'))
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>{{ session('success') }}</span>
        @if(session('undo_record') || session('undo_records'))
            <a href="{{ route('undo.restore') }}" class="btn btn-sm btn-warning">برگرداندن</a>
        @endif
    </div>
@endif

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

                            {{-- حذف گروهی --}}
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
                    <tr><td colspan="5" class="text-center">هیچ پختی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $batches->links() }}</div>
@endsection