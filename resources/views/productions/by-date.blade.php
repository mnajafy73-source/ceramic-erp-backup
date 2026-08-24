@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">جزئیات تولیدات تاریخ {{ $date }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productions.index') }}">لیست تولیدات</a></li>
            <li class="breadcrumb-item active">جزئیات {{ $date }}</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if($productions->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>اپراتور</th>
                            <th>محصول</th>
                            <th>عملیات</th>
                            <th>پرس</th>
                            <th>تعداد</th>
                            <th>زمان (ساعت)</th>
                            <th>توقف‌ها</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productions as $index => $production)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $production->operator->name ?? '-' }}</td>
                                <td>{{ $production->product->name ?? '-' }}</td>
                                <td>{{ $production->stage ?? '-' }}</td>
                                <td>{{ $production->press->name ?? '-' }}</td>
                                <td>{{ number_format($production->quantity) }}</td>
                                <td>{{ $production->time_hours ?? 0 }}</td>
                                <td>
                                    @foreach($production->stops as $stop)
                                        <span class="badge bg-warning">{{ $stop->type }}: {{ $stop->hours }}h</span>
                                    @endforeach
                                </td>
                                <td>
                                    <a href="{{ route('productions.show', $production) }}" class="btn btn-sm btn-info">مشاهده</a>
                                    <a href="{{ route('productions.edit', $production) }}" class="btn btn-sm btn-primary">ویرایش</a>
                                    <form action="{{ route('productions.destroy', $production) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این رکورد مطمئن هستید؟')">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <a href="{{ route('productions.index') }}" class="btn btn-secondary">بازگشت به لیست</a>
            </div>
        @else
            <div class="alert alert-info">هیچ تولیدی برای این تاریخ یافت نشد.</div>
        @endif
    </div>
</div>
@endsection