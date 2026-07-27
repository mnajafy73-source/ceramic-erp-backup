@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات پخت شماره {{ $firingNumber }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shuttle.index') }}">پخت‌های شاتل</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

@php
    $first = $items->first();
    $jalaliDate = $first ? \Morilog\Jalali\Jalalian::fromCarbon($first->date)->format('Y/m/d') : '—';
    $kilnLabels = ['kiln_1' => 'کوره ۱', 'kiln_2' => 'کوره ۲', 'kiln_3' => 'کوره ۳', 'packaging' => 'بسته‌بندی'];
    $subtypeLabels = ['mum' => 'موم (۹۰۰°)', 'glaze' => 'لعابدار'];
    $kilnType = $first->kiln_type ?? '';
    $subtype = $first->firing_subtype ?? null;
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <p><strong>تاریخ:</strong> {{ $jalaliDate }}</p>
        <p><strong>نوع کوره:</strong> {{ $kilnLabels[$kilnType] ?? $kilnType }}</p>
        @if($subtype)
            <p><strong>نوع پخت:</strong> {{ $subtypeLabels[$subtype] ?? $subtype }}</p>
        @endif
        <p><strong>شماره پخت:</strong> {{ $firingNumber }}</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>محصول</th>
                        <th>تعداد خروجی</th>
                        <th>بسته‌بندی</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? '—' }}</td>
                        <td>{{ $item->output_quantity !== null ? rtrim(rtrim(number_format($item->output_quantity, 2, '.', ''), '0'), '.') : '—' }}</td>
                        <td>{{ $item->is_packaged ? 'بله' : 'خیر' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('shuttle.index') }}" class="btn btn-secondary">بازگشت</a>
    <a href="{{ route('shuttle.edit', ['firingNumber' => $firingNumber, 'date' => $items->first()->date->format('Y-m-d'), 'kiln_type' => $kilnType]) }}" class="btn btn-warning ms-2">ویرایش</a>
</div>
@endsection