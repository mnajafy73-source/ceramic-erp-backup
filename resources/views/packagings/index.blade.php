@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">کارتن و لایه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('packagings.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت کارتن/لایه جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>نام</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packagings as $packaging)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $packaging->type == 'carton' ? 'کارتن' : 'لایه' }}</td>
                    <td>{{ $packaging->name }}</td>
                    <td>
                        <a href="{{ route('packagings.edit', $packaging) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('packagings.destroy', $packaging) }}" method="POST" class="d-inline">
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
                    <td colspan="4" class="text-center">هیچ کارتن یا لایه‌ای ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection