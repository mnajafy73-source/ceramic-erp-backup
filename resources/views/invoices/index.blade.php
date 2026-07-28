@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">لیست حواله‌های باز</h4>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> حواله جدید</a>
</div>

{{-- پیام موفقیت و خطا --}}
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

{{-- لیست حواله‌ها --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>شماره حواله</th>
                        <th>تاریخ</th>
                        <th>مشتری</th>
                        <th>تعداد محصولات</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td><span class="badge bg-primary">{{ $invoice->display_number }}</span></td>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($invoice->date)->format('Y/m/d') }}</td>
                        <td>{{ $invoice->customer_name }}</td>
                        <td>{{ $invoice->products->count() }}</td>
                        <td>
                            <span class="badge {{ $invoice->status == 'open' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $invoice->status == 'open' ? 'باز' : 'بسته' }}
                            </span>
                        </td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" onsubmit="return confirm('مطمئن هستید این حواله حذف شود؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">هیچ حواله‌ای موجود نیست.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection