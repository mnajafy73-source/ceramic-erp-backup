@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">لیست تولیدات</h4>
    <a href="{{ route('productions.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>تاریخ</th>
                        <th>تعداد رکوردها</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $item)
                    <tr>
                        <td>{{ \Morilog\Jalali\Jalalian::fromCarbon($item->date)->format('Y/m/d') }}</td>
                        <td>{{ $item->total }}</td>
                        <td>
                            <a href="{{ route('productions.show-by-date', ['date' => $item->date->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i> مشاهده
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">هیچ تولیدی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $productions->links() }}</div>
@endsection