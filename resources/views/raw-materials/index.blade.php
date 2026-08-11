@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">مواد اولیه</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('raw-materials.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت ماده اولیه جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام ماده</th>
                    <th>واحد</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $material->name }}</td>
                    <td>{{ $material->unit == 'kg' ? 'کیلوگرم' : 'تن' }}</td>
                    <td>
                        <a href="{{ route('raw-materials.edit', $material) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('raw-materials.destroy', $material) }}" method="POST" class="d-inline">
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
                    <td colspan="4" class="text-center">هیچ ماده اولیه‌ای ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection