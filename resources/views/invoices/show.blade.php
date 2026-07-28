@php use Morilog\Jalali\Jalalian; @endphp

@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">مشاهده حواله شماره {{ $invoice->display_number }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">حواله‌ها</a></li>
            <li class="breadcrumb-item active">مشاهده</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p><strong>شماره حواله:</strong> {{ $invoice->display_number }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>تاریخ:</strong> {{ Jalalian::fromCarbon($invoice->date)->format('Y/m/d') }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>مشتری:</strong> {{ $invoice->customer_name }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>وضعیت:</strong> 
                    <span class="badge {{ $invoice->status == 'open' ? 'bg-success' : 'bg-secondary' }}">
                        {{ $invoice->status == 'open' ? 'باز' : 'بسته' }}
                    </span>
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
                        <th>کارتن</th>
                        <th>لایه</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->products as $item)
                    <tr>
                        <td>{{ $item->product->name ?? '—' }}</td>
                        <td>{{ number_format($item->quantity, 0) }}</td>
                        <td>{{ $item->box }}</td>
                        <td>{{ $item->layer }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">بازگشت</a>
    @if($invoice->status == 'open')
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning ms-2">ویرایش</a>
    @endif
</div>
@endsection