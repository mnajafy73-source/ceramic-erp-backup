@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات پخت تونلی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('tonneli.index') }}">پخت‌های تونلی</a></li>
            <li class="breadcrumb-item active">جزئیات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>تاریخ:</strong> {{ $tonneli->jalali_date ?? '—' }}</p>
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
                        <th>ورودی</th>
                        <th>خروجی</th>
                        <th>بسته‌بندی</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tonneli->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? '—' }}</td>
                        <td>{{ $item->input_quantity }}</td>
                        <td>{{ $item->output_quantity }}</td>
                        <td>{{ $item->is_packaged ? 'بله' : 'خیر' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">هیچ محصولی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('tonneli.index') }}" class="btn btn-secondary">بازگشت</a>
    <a href="{{ route('tonneli.edit', $tonneli) }}" class="btn btn-warning ms-2">ویرایش</a>
</div>
@endsection