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
    <!-- واردات خودکار -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-robot me-2"></i>واردات خودکار از مسیر</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    با کلیک روی دکمه زیر، تمام برگه‌های استاندارد (تولید، کوره تونلی، کوره شاتل، فروش رسمی، فروش غیررسمی، شانه زنی و مواد سازی) از فایل اکسل تنظیم‌شده در فایل <code>.env</code> وارد می‌شوند.
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

    <!-- واردات دستی -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-hand-holding me-2"></i>واردات دستی برگه‌ها</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- تولید -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-industry fa-2x text-primary mb-2"></i>
                                <h6>تولید</h6>
                                <form action="{{ route('import.productions') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- کوره تونلی -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-fire fa-2x text-danger mb-2"></i>
                                <h6>کوره تونلی</h6>
                                <form action="{{ route('import.tonneli') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-danger w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- کوره شاتل -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-train fa-2x text-warning mb-2"></i>
                                <h6>کوره شاتل</h6>
                                <form action="{{ route('import.shuttle') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-warning w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- فروش غیررسمی -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-file-invoice fa-2x text-success mb-2"></i>
                                <h6>فروش غیررسمی</h6>
                                <form action="{{ route('import.informal-sales') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-success w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- فروش رسمی -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-file-invoice-dollar fa-2x text-info mb-2"></i>
                                <h6>فروش رسمی</h6>
                                <form action="{{ route('import.formal-sales') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-info w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- شانه زنی -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-hand-sparkles fa-2x text-purple mb-2"></i>
                                <h6>شانه زنی</h6>
                                <form action="{{ route('import.shoulder') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-purple w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- مواد سازی -->
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-flask fa-2x text-secondary mb-2"></i>
                                <h6>مواد سازی</h6>
                                <form action="{{ route('import.material-making') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".xlsx,.xls" required>
                                    <button type="submit" class="btn btn-sm btn-secondary w-100">وارد کردن</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .text-purple { color: #6f42c1; }
    .btn-purple { background-color: #6f42c1; color: white; border-color: #6f42c1; }
    .btn-purple:hover { background-color: #5a32a3; border-color: #5a32a3; color: white; }
</style>
@endpush