@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">مدیریت مشتریان</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">مشتریان</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> ثبت مشتری جدید
            </a>
        </div>

        @if($customers->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>نام مشتری</th>
                            <th>تلفن</th>
                            <th>آدرس</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $index => $customer)
                            <tr>
                                <td>{{ $customers->firstItem() + $index }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->phone ?? '-' }}</td>
                                <td>{{ $customer->address ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $customer->status ? 'bg-success' : 'bg-danger' }}">
                                        {{ $customer->status ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> ویرایش
                                    </a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این مشتری مطمئن هستید؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $customers->links() }}
            </div>
        @else
            <div class="alert alert-info">هیچ مشتری ثبت نشده است.</div>
        @endif
    </div>
</div>
@endsection