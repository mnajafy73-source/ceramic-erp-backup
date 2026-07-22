@extends('layouts.app')

@section('title', 'جزئیات تولید')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات تولید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productions.index') }}">تولیدات</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th>تاریخ:</th><td>{{ \Morilog\Jalali\Jalalian::fromCarbon($production->date)->format('Y/m/d') }}</td></tr>
            <tr><th>محصول:</th><td>{{ $production->product->name ?? 'بدون محصول' }}</td></tr>
            <tr><th>اپراتور:</th><td>{{ $production->operator->name ?? 'بدون اپراتور' }}</td></tr>
            <tr><th>پرس:</th><td>{{ $production->press->name ?? 'بدون پرس' }}</td></tr>
            <tr><th>عملیات:</th><td>{{ $production->stage == 'production' ? 'تولید' : ($production->stage == 'payment' ? 'پرداخت' : 'بسته‌بندی') }}</td></tr>
            <tr><th>تعداد:</th><td>{{ rtrim(rtrim(number_format($production->quantity, 2, '.', ''), '0'), '.') }}</td></tr>
            <tr><th>زمان (ساعت):</th><td>{{ $production->time_hours !== null ? rtrim(rtrim(number_format($production->time_hours, 2, '.', ''), '0'), '.') : '—' }}</td></tr>
        </table>

        @if($production->stops->isNotEmpty())
        <h6 class="fw-bold mt-4">توقف‌ها</h6>
        <ul class="list-group">
            @foreach($production->stops as $stop)
            <li class="list-group-item">
                {{ $stop->type == 'machine_failure' ? 'خرابی دستگاه' : 'تعویض/تعمیر قالب' }}
                — {{ rtrim(rtrim(number_format($stop->hours, 2, '.', ''), '0'), '.') }} ساعت
            </li>
            @endforeach
        </ul>
        @endif

        <div class="mt-3">
            <a href="{{ route('productions.index') }}" class="btn btn-secondary">بازگشت</a>
            <a href="{{ route('productions.edit', $production) }}" class="btn btn-warning ms-2">ویرایش</a>
        </div>
    </div>
</div>
@endsection