@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <h4 class="mb-0 fw-bold">خوش آمدید، {{ Auth::user()->name ?? 'کاربر' }}</h4>
        <p class="text-muted">خلاصه وضعیت امروز کارخانه</p>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">تولید امروز</h6>
                        <h3 class="mb-0">۰</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-industry text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">پخت کوره‌ها</h6>
                        <h3 class="mb-0">۰</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-fire text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">فروش امروز</h6>
                        <h3 class="mb-0">۰ ریال</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-shopping-cart text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">محصولات</h6>
                        <h3 class="mb-0">۰</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                        <i class="fas fa-boxes text-info fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection