@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت فروش غیررسمی جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('informal-sales.index') }}">فروش غیررسمی</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('informal-sales.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <!-- تاریخ شمسی -->
                <div class="col-md-4">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', $today) }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- مشتری -->
                <div class="col-md-4">
                    <label class="form-label">مشتری</label>
                    <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                        <option value="">انتخاب مشتری</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- نام مشتری جدید -->
                <div class="col-md-4">
                    <label class="form-label">نام مشتری جدید</label>
                    <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                           value="{{ old('customer_name') }}" placeholder="در صورت عدم انتخاب مشتری">
                    @error('customer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- وضعیت -->
                <div class="col-md-4">
                    <label class="form-label">وضعیت</label>
                    <select name="status" class="form-select">
                        <option value="unpaid" {{ old('status') == 'unpaid' ? 'selected' : '' }}>پرداخت نشده</option>
                        <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                        <option value="canceled" {{ old('status') == 'canceled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                </div>
            </div>

            <hr class="mt-4">

            <!-- ===== آیتم‌های فاکتور ===== -->
            <h6 class="fw-bold">آیتم‌های فاکتور</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>محصول</th>
                            <th>تعداد</th>
                            <th>قیمت واحد (ریال)</th>
                            <th>قیمت کل</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        <tr class="item-row">
                            <td>
                                <select name="items[0][product_id]" class="form-select" required>
                                    <option value="">انتخاب محصول</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][quantity]" class="form-control quantity" placeholder="تعداد" step="1" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="items[0][unit_price]" class="form-control unit-price" placeholder="قیمت واحد" step="1000" min="0" required>
                            </td>
                            <td>
                                <input type="text" class="form-control item-total" readonly>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-row" style="display:none;">✖</button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="3" class="text-end">جمع کل:</th>
                            <th id="grand-total">0</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-secondary" id="add-row">
                    <i class="fas fa-plus-circle"></i> افزودن ردیف
                </button>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">ثبت فروش</button>
                <a href="{{ route('informal-sales.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let rowIndex = 1;

    document.getElementById('add-row').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const html = `
            <tr class="item-row">
                <td>
                    <select name="items[${rowIndex}][product_id]" class="form-select" required>
                        <option value="">انتخاب محصول</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][quantity]" class="form-control quantity" placeholder="تعداد" step="1" min="1" required>
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][unit_price]" class="form-control unit-price" placeholder="قیمت واحد" step="1000" min="0" required>
                </td>
                <td>
                    <input type="text" class="form-control item-total" readonly>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">✖</button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        updateRemoveButtons();
        attachCalculationEvents();
    });

    document.getElementById('items-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
                updateRemoveButtons();
                calculateGrandTotal();
            }
        }
    });

    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const btn = row.querySelector('.remove-row');
            if (index === 0 && rows.length === 1) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'inline-block';
            }
        });
    }

    function attachCalculationEvents() {
        document.querySelectorAll('.quantity, .unit-price').forEach(el => {
            el.removeEventListener('input', calculateRowTotal);
            el.addEventListener('input', calculateRowTotal);
        });
    }

    function calculateRowTotal(e) {
        const row = e.target.closest('.item-row');
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const total = quantity * unitPrice;
        row.querySelector('.item-total').value = total.toLocaleString();
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        const totals = document.querySelectorAll('.item-total');
        let sum = 0;
        totals.forEach(input => {
            const val = parseFloat(input.value.replace(/,/g, '')) || 0;
            sum += val;
        });
        document.getElementById('grand-total').textContent = sum.toLocaleString();
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateRemoveButtons();
        attachCalculationEvents();
    });
</script>
@endpush
@endsection