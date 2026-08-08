@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات خرید کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('packaging-purchases.index') }}">خرید کارتن و لایه</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>تاریخ خرید</th><td>{{ jdate($packagingPurchase->purchase_date)->format('Y/m/d') }}</td></tr>
                    <tr><th>تأمین‌کننده</th><td>{{ $packagingPurchase->supplier ?? '-' }}</td></tr>
                    <tr><th>هزینه حمل‌ونقل</th><td>{{ number_format($packagingPurchase->total_transport_cost) }} ریال</td></tr>
                </table>
            </div>
        </div>

        <h5 class="fw-bold">اقلام خریداری‌شده</h5>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>نوع</th>
                    <th>تعداد</th>
                    <th>قیمت کل (ریال)</th>
                    <th>قیمت هر عدد (ریال)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($packagingPurchase->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->packaging->name }}</td>
                    <td>{{ $item->packaging->type == 'carton' ? 'کارتن' : 'لایه' }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                    <td>{{ number_format($item->total_price) }}</td>
                    <td>{{ number_format($item->price_per_unit, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <a href="{{ route('packaging-purchases.index') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
</div>
@endsection