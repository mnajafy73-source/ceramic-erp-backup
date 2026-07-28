@extends('layouts.app')

@push('scripts')
<script>
    let productIndex = 0;

    function addProductRow() {
        const container = document.getElementById('products-container');
        if (!container) return;
        const html = `
            <div class="row g-2 mb-2 product-row" id="product-row-${productIndex}">
                <div class="col-md-5">
                    <select name="products[${productIndex}][product_id]" class="form-select" required>
                        <option value="">انتخاب محصول...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" name="products[${productIndex}][quantity]" class="form-control" placeholder="تعداد" step="0.01" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        productIndex++;
    }

    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (typeof $ !== 'undefined' && $.fn.persianDatepicker) {
                $('#date').persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    initialValue: false,
                    observer: true,
                    calendar: { persian: { locale: 'fa' } }
                });
            }
        } catch (e) {}
        addProductRow();
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت حواله جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">حواله‌ها</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('invoices.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                           value="{{ old('date', $today) }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">شماره حواله</label>
                    <input type="text" class="form-control bg-light" value="{{ $displayNumber }}" disabled>
                    <small class="text-muted">شماره به‌طور خودکار تولید می‌شود.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نام مشتری <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" 
                           value="{{ old('customer_name') }}" required>
                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">➕ محصولات این حواله</h6>
                    <div id="products-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addProductRow()">
                        <i class="fas fa-plus-circle"></i> افزودن محصول
                    </button>
                    <small class="d-block mt-2 text-muted">کارتن و لایه به‌طور خودکار از اطلاعات محصول محاسبه می‌شوند.</small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary ms-2">بازگشت</a>
        </form>
    </div>
</div>
@endsection