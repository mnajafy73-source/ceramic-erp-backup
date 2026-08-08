@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست خرید مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">خرید مواد</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('raw-material-purchases.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت خرید جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تاریخ</th>
                    <th>تأمین‌کننده</th>
                    <th>مواد خریداری‌شده</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ jdate($purchase->purchase_date)->format('Y/m/d') }}</td>
                    <td>{{ $purchase->supplier ?? '-' }}</td>
                    <td>
                        @foreach($purchase->items as $item)
                            <span class="badge bg-info">{{ $item->rawMaterial->name }} ({{ number_format($item->quantity, 2) }} کیلو)</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('raw-material-purchases.show', $purchase) }}" class="btn btn-sm btn-success">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('raw-material-purchases.edit', $purchase) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('raw-material-purchases.destroy', $purchase) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا مطمئن هستید؟')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">هیچ خریدی ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection