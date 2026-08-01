@extends('layouts.app')

@push('scripts')
<script>
    let productIndex = {{ $informal_sale->products->count() }};

    function addProductRow(productId = '', quantity = '', unitPrice = '') {
        const container = document.getElementById('products-container');
        if (!container) return;
        const html = `
            <div class="row g-2 mb-2 product-row">
                <div class="col-md-4">
                    <select name="products[${productIndex}][product_id]" class="form-select" required>
                        <option value="">انتخاب محصول...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" ${productId == {{ $p->id }} ? 'selected' : ''}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="products[${productIndex}][quantity]" class="form-control" placeholder="تعداد" step="0.01" value="${quantity}" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="products[${productIndex}][unit_price]" class="form-control" placeholder="قیمت واحد" step="0.01" value="${unitPrice}" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-row').remove(); calculateTotal();">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        productIndex++;
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.product-row').forEach(row => {
            const qty = parseFloat(row.querySelector('input[name$="[quantity]"]')?.value) || 0;
            const price = parseFloat(row.querySelector('input[name$="[unit_price]"]')?.value) || 0;
            total += qty * price;
        });
        document.getElementById('total_price').textContent = total.toLocaleString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        @foreach($informal_sale->products as $item)
            addProductRow({{ $item->product_id }}, '{{ $item->quantity }}', '{{ $item->unit_price }}');
        @endforeach

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

        document.addEventListener('change', calculateTotal);
        document.addEventListener('input', calculateTotal);
        calculateTotal();
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش فاکتور غیررسمی شماره {{ $informal_sale->display_number }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('informal-sales.index') }}">فاکتورها</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('informal-sales.update', $informal_sale) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                           value="{{ old('date', $informal_sale->jalali_date) }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">شماره فاکتور</label>
                    <input type="text" class="form-control bg-light" value="{{ $informal_sale->display_number }}" disabled>
                    <small class="text-muted">شماره قابل تغییر نیست.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نام مشتری <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" 
                           value="{{ old('customer_name', $informal_sale->customer_name) }}" required>
                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">➕ محصولات این فاکتور</h6>
                    <div id="products-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addProductRow(); calculateTotal();">
                        <i class="fas fa-plus-circle"></i> افزودن محصول
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <p><strong>جمع کل:</strong> <span id="total_price">0</span></p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> بروزرسانی</button>
            <a href="{{ route('informal-sales.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection