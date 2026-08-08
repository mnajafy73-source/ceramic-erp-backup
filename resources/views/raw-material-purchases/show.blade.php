@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات خرید مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('raw-material-purchases.index') }}">خرید مواد</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>تاریخ خرید</th><td>{{ jdate($rawMaterialPurchase->purchase_date)->format('Y/m/d') }}</td></tr>
                    <tr><th>تأمین‌کننده</th><td>{{ $rawMaterialPurchase->supplier ?? '-' }}</td></tr>
                    <tr><th>هزینه حمل‌ونقل</th><td>{{ number_format($rawMaterialPurchase->total_transport_cost) }} ریال</td></tr>
                </table>
            </div>
        </div>

        <h5 class="fw-bold">مواد خریداری‌شده</h5>
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ماده</th>
                    <th>مقدار (کیلوگرم)</th>
                    <th>قیمت کل (ریال)</th>
                    <th>قیمت هر گرم (ریال)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rawMaterialPurchase->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->rawMaterial->name }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ number_format($item->total_price) }}</td>
                    <td>{{ number_format($item->price_per_gram, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <a href="{{ route('raw-material-purchases.index') }}" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>
</div>
@endsection