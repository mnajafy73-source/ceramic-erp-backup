@extends('layouts.app')

@push('scripts')
<script>
    let productIndex = {{ $sale->products->count() }};

    function addProductRow(productId = '', quantity = '', unitPrice = '') {
        const container = document.getElementById('products-container');
        if (!container) return;
        const html = `
            <div class="row g-2 mb-2 product-row" id="product-row-${productIndex}">
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
        const tax = parseFloat(document.getElementById('tax_percent').value) || 0;
        const totalWithTax = total + (total * tax / 100);
        document.getElementById('total_price').textContent = total.toLocaleString();
        document.getElementById('total_with_tax').textContent = totalWithTax.toLocaleString();
    }

    // AJAX برای دریافت محصولات حواله (در ویرایش هم کار می‌کند)
    document.addEventListener('DOMContentLoaded', function() {
        const invoiceSelect = document.getElementById('invoice_id');
        invoiceSelect.addEventListener('change', function() {
            const invoiceId = this.value;
            if (!invoiceId) return;

            fetch(`/sales/invoice-products/${invoiceId}`)
                .then(response => response.json())
                .then(data => {
                    // حذف ردیف‌های قبلی
                    document.querySelectorAll('.product-row').forEach(row => row.remove());
                    productIndex = 0;
                    data.forEach(item => {
                        addProductRow(item.product_id, item.quantity, 0);
                    });
                    calculateTotal();
                })
                .catch(error => console.error('خطا در دریافت محصولات:', error));
        });

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

        @foreach($sale->products as $item)
            addProductRow({{ $item->product_id }}, '{{ $item->quantity }}', '{{ $item->unit_price }}');
        @endforeach

        document.addEventListener('change', calculateTotal);
        document.addEventListener('input', calculateTotal);
        calculateTotal();
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش فاکتور شماره {{ $sale->invoice_number }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">فاکتورها</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('sales.update', $sale) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                           value="{{ old('date', $sale->jalali_date) }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">شماره فاکتور <span class="text-danger">*</span></label>
                    <input type="number" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" 
                           value="{{ old('invoice_number', $sale->invoice_number) }}" required>
                    @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">نام مشتری <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror" 
                           value="{{ old('customer_name', $sale->customer_name) }}" required>
                    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">درصد مالیات</label>
                    <input type="number" name="tax_percent" id="tax_percent" class="form-control @error('tax_percent') is-invalid @enderror" 
                           value="{{ old('tax_percent', $sale->tax_percent) }}" step="0.01" min="0" max="100">
                    @error('tax_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">حواله (اختیاری)</label>
                    <select name="invoice_id" id="invoice_id" class="form-select">
                        <option value="">بدون حواله</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" {{ old('invoice_id', $sale->invoice_id) == $invoice->id ? 'selected' : '' }}>
                                {{ $invoice->display_number }} - {{ \Morilog\Jalali\Jalalian::fromCarbon($invoice->date)->format('Y/m/d') }} - {{ $invoice->customer_name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">اگر حواله انتخاب شود، موجودی انبار کم نمی‌شود و محصولات آن به‌طور خودکار وارد می‌شوند.</small>
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
                    <p><strong>جمع کل (بدون مالیات):</strong> <span id="total_price">0</span></p>
                </div>
                <div class="col-md-4">
                    <p><strong>جمع کل با مالیات:</strong> <span id="total_with_tax">0</span></p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> بروزرسانی</button>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection