@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">موجودی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">موجودی</li>
        </ol>
    </nav>
</div>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-cube fa-3x text-primary mb-3"></i>
                <h5 class="card-title">موجودی خام</h5>
                <p class="card-text text-muted">محصولات تولیدشده با پرس</p>
                <a href="{{ route('inventory.raw') }}" class="btn btn-primary">مشاهده</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-fire fa-3x text-warning mb-3"></i>
                <h5 class="card-title">موجودی موم (۹۰۰°)</h5>
                <p class="card-text text-muted">پخت شاتل کوره ۳ - موم</p>
                <a href="{{ route('inventory.mum') }}" class="btn btn-warning">مشاهده</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-fire fa-3x text-danger mb-3"></i>
                <h5 class="card-title">موجودی ۱۳۰۰°</h5>
                <p class="card-text text-muted">پخت شاتل کوره ۲</p>
                <a href="{{ route('inventory.glaze1300') }}" class="btn btn-danger">مشاهده</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="fas fa-warehouse fa-3x text-success mb-3"></i>
                <h5 class="card-title">موجودی انبار</h5>
                <p class="card-text text-muted">محصولات بسته‌بندی‌شده نهایی</p>
                <a href="{{ route('inventory.warehouse') }}" class="btn btn-success">مشاهده</a>
            </div>
        </div>
    </div>
</div>
@endsection