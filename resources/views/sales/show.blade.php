@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">مشاهده فاکتور شماره {{ $sale->invoice_number }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">فاکتورها</a></li>
            <li class="breadcrumb-item active">مشاهده</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p><strong>شماره فاکتور:</strong> {{ $sale->invoice_number }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>تاریخ:</strong> {{ \Morilog\Jalali\Jalalian::fromCarbon($sale->date)->format('Y/m/d') }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>مشتری:</strong> {{ $sale->customer_name }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>وضعیت:</strong> 
                    @php
                        $statusLabels = ['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'cancelled' => 'باطل شده'];
                        $statusColors = ['pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger'];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$sale->status] }}">
                        {{ $statusLabels[$sale->status] }}
                    </span>
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <p><strong>درصد مالیات:</strong> {{ $sale->tax_percent }}%</p>
            </div>
            <div class="col-md-3">
                <p><strong>جمع کل (بدون مالیات):</strong> {{ number_format($sale->total_price, 0) }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>جمع کل با مالیات:</strong> {{ number_format($sale->total_with_tax, 0) }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>حواله:</strong> 
                    @if($sale->invoice)
                        {{ $sale->invoice->display_number }}
                    @else
                        <span class="text-muted">بدون حواله</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>نام محصول</th>
                        <th>تعداد</th>
                        <th>قیمت واحد</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->products as $item)
                    <tr>
                        <td>{{ $item->product->name ?? '—' }}</td>
                        <td>{{ number_format($item->quantity, 0) }}</td>
                        <td>{{ number_format($item->unit_price, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('sales.index') }}" class="btn btn-secondary">بازگشت</a>
    @if($sale->status == 'pending')
        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-warning ms-2">ویرایش</a>
    @endif
</div>
@endsection