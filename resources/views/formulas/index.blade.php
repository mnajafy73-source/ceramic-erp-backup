@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">لیست فرمول‌ها</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">فرمول‌ها</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <a href="{{ route('formulas.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> ثبت فرمول جدید
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام فرمول</th>
                    <th>مواد تشکیل‌دهنده</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($formulas as $formula)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $formula->name }}</td>
                    <td>
                        @foreach($formula->items as $item)
                            <span class="badge bg-info">{{ $item->rawMaterial->name }} ({{ $item->percentage }}%)</span>
                        @endforeach
                    </td>
                    <td>
                        <a href="{{ route('formulas.edit', $formula) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('formulas.destroy', $formula) }}" method="POST" class="d-inline">
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
                    <td colspan="4" class="text-center">هیچ فرمولی ثبت نشده است.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection