@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">📤 واردات مواد سازی از اکسل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('material-making.index') }}">مواد سازی</a></li>
            <li class="breadcrumb-item active">واردات</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            فرمت فایل اکسل باید شامل ستون‌های زیر باشد:
            <ul class="mb-0 mt-2">
                <li><strong>سال</strong> - مثال: 1405</li>
                <li><strong>ماه</strong> - مثال: 5</li>
                <li><strong>روز</strong> - مثال: 2</li>
                <li><strong>نام</strong> - اختیاری</li>
                <li><strong>فرمول</strong> - نام فرمول (مثلاً بلسن-نسوز)</li>
                <li><strong>تعداد بالمیل</strong> - عدد</li>
                <li><strong>وزن بالمیل (کیلوگرم)</strong> - عدد</li>
            </ul>
        </div>

        <form action="{{ route('material-making.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">فایل اکسل <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> وارد کردن
                    </button>
                    <a href="{{ route('material-making.index') }}" class="btn btn-secondary">انصراف</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection