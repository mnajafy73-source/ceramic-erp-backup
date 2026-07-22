@extends('layouts.app')

@section('title', 'ایجاد پرس')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ایجاد پرس جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('presses.index') }}">پرس‌ها</a></li>
            <li class="breadcrumb-item active">ایجاد</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('presses.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">نام پرس <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="status">فعال</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ذخیره</button>
            <a href="{{ route('presses.index') }}" class="btn btn-secondary me-2">انصراف</a>
        </form>
    </div>
</div>
@endsection