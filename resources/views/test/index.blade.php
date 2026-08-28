@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">تست ثبت فروش غیررسمی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">تست</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('test.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نام مشتری <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                           value="{{ old('customer_name', 'مشتری تست') }}" required>
                    @error('customer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">نام محصول <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
                           value="{{ old('product_name', 'بلسن تست') }}" required>
                    @error('product_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">تعداد <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                           value="{{ old('quantity', 10) }}" required min="1">
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">قیمت واحد (ریال) <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control @error('unit_price') is-invalid @enderror"
                           value="{{ old('unit_price', 100000) }}" required min="0">
                    @error('unit_price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">ثبت تست</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">بازگشت</a>
            </div>
        </form>

        <hr class="mt-4">

        <div class="mt-3">
            <h6>راهنمای تست:</h6>
            <ul class="text-muted small">
                <li>این فرم یک فروش غیررسمی را در دیتابیس ثبت می‌کند.</li>
                <li>اگر ثبت شد، یعنی سیستم فروش غیررسمی سالم است.</li>
                <li>پس از ثبت، به بخش <strong>فروش → فروش غیررسمی</strong> بروید و رکورد را ببینید.</li>
                <li>اگر خطایی رخ داد، پیام خطا را برای من بفرستید.</li>
            </ul>
        </div>
    </div>
</div>
@endsection