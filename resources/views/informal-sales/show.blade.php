@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات فروش غیررسمی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('informal-sales.index') }}">فروش غیررسمی</a></li>
            <li class="breadcrumb-item active">شماره {{ $informalSale->display_number }}</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        {{-- اطلاعات اصلی فاکتور --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="fw-bold">شماره فاکتور:</label>
                <span>{{ $informalSale->display_number }}</span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">تاریخ:</label>
                <span>{{ $informalSale->jalali_date }}</span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">مشتری:</label>
                <span>{{ $informalSale->customer_name ?? 'نامشخص' }}</span>
            </div>
            <div class="col-md-3">
                <label class="fw-bold">وضعیت:</label>
                @php
                    $statusLabels = [
                        'unpaid'    => 'پرداخت نشده',
                        'paid'      => 'پرداخت شده',
                        'canceled'  => 'لغو شده',
                    ];
                    $statusClass = [
                        'unpaid'    => 'bg-warning',
                        'paid'      => 'bg-success',
                        'canceled'  => 'bg-danger',
                    ];
                    $status = $informalSale->status ?? 'unpaid';
                @endphp
                <span class="badge {{ $statusClass[$status] ?? 'bg-secondary' }}">
                    {{ $statusLabels[$status] ?? 'نامشخص' }}
                </span>
            </div>
        </div>

        <hr>

        {{-- جدول آیتم‌های فاکتور --}}
        <h6 class="fw-bold mb-3">آیتم‌های فاکتور</h6>
        @if($informalSale->products->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>محصول</th>
                            <th>تعداد</th>
                            <th>قیمت واحد (ریال)</th>
                            <th>قیمت کل (ریال)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($informalSale->products as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->product->name ?? 'نامشخص' }}</td>
                                <td>{{ number_format($item->quantity) }}</td>
                                <td>{{ number_format($item->unit_price) }}</td>
                                <td>{{ number_format($item->quantity * $item->unit_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="4" class="text-end">جمع کل:</th>
                            <th>{{ number_format($informalSale->total_price) }} ریال</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="alert alert-warning">هیچ آیتمی برای این فاکتور ثبت نشده است.</div>
        @endif

        <div class="mt-3 d-flex gap-2">
            <a href="{{ route('informal-sales.index') }}" class="btn btn-secondary">بازگشت به لیست</a>
            <a href="{{ route('informal-sales.edit', $informalSale) }}" class="btn btn-primary">ویرایش</a>
            <form action="{{ route('informal-sales.destroy', $informalSale) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('آیا از حذف این فاکتور مطمئن هستید؟')">حذف</button>
            </form>
            @if($informalSale->status !== 'paid')
                <form action="{{ route('informal-sales.paid', $informalSale) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">تغییر به پرداخت شده</button>
                </form>
            @endif
            @if($informalSale->status !== 'canceled')
                <form action="{{ route('informal-sales.cancel', $informalSale) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary">لغو فاکتور</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection