@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">واردات از اکسل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">واردات</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
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

<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-robot me-2"></i>واردات خودکار از مسیر</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    با کلیک روی دکمه زیر، تمام برگه‌های استاندارد (تولید، کوره تونلی، کوره شاتل، فروش رسمی، فروش غیررسمی، شانه زنی و مواد سازی) از فایل اکسل تنظیم‌شده در فایل <code>.env</code> وارد می‌شوند و موجودی‌ها به‌روز می‌شوند.
                </p>
                <form action="{{ route('import.from-path') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-play me-2"></i> شروع واردات خودکار
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection