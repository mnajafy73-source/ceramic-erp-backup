@extends('layouts.app')

@section('title', 'تاریخچه تغییرات کالاها')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0">تاریخچه تغییرات</h4>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('product_logs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="جستجوی نام کالا..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            @if(request('search'))
            <div class="col-md-2">
                <a href="{{ route('product_logs.index') }}" class="btn btn-outline-secondary w-100">پاک کردن</a>
            </div>
            @endif
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>تاریخ</th>
                    <th>کاربر</th>
                    <th>کالا</th>
                    <th>عملیات</th>
                    <th>تغییرات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i') }}</td>
                    <td>{{ $log->user->name ?? '—' }}</td>
                    <td>{{ $log->product->name ?? '—' }}</td>
                    <td>
                        @if($log->action == 'create') ایجاد
                        @elseif($log->action == 'update') ویرایش
                        @elseif($log->action == 'delete') حذف
                        @else {{ $log->action }}
                        @endif
                    </td>
                    <td>{{ $log->persian_changes ?: '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">تاریخچه‌ای یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $logs->links() }}</div>
@endsection