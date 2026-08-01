@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">فاکتورهای فروش غیررسمی</h4>
    <a href="{{ route('informal-sales.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> فاکتور جدید</a>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <span>{{ session('success') }}</span>
        @if(session('undo_record'))
            <a href="{{ route('undo.restore') }}" class="btn btn-sm btn-warning">برگرداندن</a>
        @endif
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>شماره فاکتور</th>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>جمع کل</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td><span class="badge bg-primary">{{ $sale->display_number }}</span></td>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($sale->date)->format('Y/m/d') }}</td>
                        <td>{{ $sale->customer_name }}</td>
                        <td>{{ number_format($sale->total_price, 0) }}</td>
                        <td>
                            @php
                                $statusLabels = ['pending' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'cancelled' => 'باطل شده'];
                                $statusColors = ['pending' => 'warning', 'paid' => 'success', 'cancelled' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$sale->status] }}">
                                {{ $statusLabels[$sale->status] }}
                            </span>
                        </td>
                        <td class="d-flex gap-1 flex-wrap">
                            <a href="{{ route('informal-sales.show', $sale) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>

                            {{-- ویرایش فقط برای pending --}}
                            @if($sale->status == 'pending')
                                <a href="{{ route('informal-sales.edit', $sale) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                            @endif

                            {{-- حذف برای pending و cancelled --}}
                            @if($sale->status == 'pending' || $sale->status == 'cancelled')
                                <form action="{{ route('informal-sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('مطمئن هستید این فاکتور حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif

                            {{-- دکمه پرداخت فقط برای pending --}}
                            @if($sale->status == 'pending')
                                <form action="{{ route('informal-sales.paid', $sale) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">پرداخت شد</button>
                                </form>
                            @endif

                            {{-- دکمه باطل کردن فقط برای pending --}}
                            @if($sale->status == 'pending')
                                <form action="{{ route('informal-sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('با باطل کردن این فاکتور، موجودی به حالت قبل برمی‌گردد. ادامه می‌دهید؟')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">باطل کن</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">هیچ فاکتوری یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $sales->links() }}</div>
@endsection