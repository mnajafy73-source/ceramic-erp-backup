@extends('layouts.app')

@push('scripts')
<script>
    async function loadFiringNumber() {
        const kiln = document.getElementById('kiln_type')?.value;
        const date = document.getElementById('date')?.value;
        const display = document.getElementById('firing-number-display');
        if (!display) return;

        if (!kiln || !date) {
            display.innerText = '—';
            return;
        }

        try {
            const response = await fetch(`/shuttle/next-firing-number?kiln_type=${encodeURIComponent(kiln)}&date=${encodeURIComponent(date)}`);
            if (!response.ok) throw new Error('Network error');
            const data = await response.json();
            display.innerText = data.number ?? '—';
        } catch (error) {
            display.innerText = '—';
        }
    }

    function toggleSubtype() {
        const kilnType = document.getElementById('kiln_type')?.value;
        const subtypeGroup = document.getElementById('subtype-group');
        if (!subtypeGroup) return;
        if (kilnType === 'kiln_3') {
            subtypeGroup.style.display = 'block';
        } else {
            subtypeGroup.style.display = 'none';
            document.getElementById('firing_subtype').value = '';
        }
    }

    let productIndex = 0;
    function addProductRow() {
        const container = document.getElementById('products-container');
        if (!container) return;
        const html = `
            <div class="row g-2 mb-2 product-row" id="product-row-${productIndex}">
                <div class="col-md-4">
                    <select name="products[${productIndex}][product_id]" class="form-select" required>
                        <option value="">انتخاب محصول...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="products[${productIndex}][output_quantity]" class="form-control" placeholder="تعداد" step="0.01">
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input type="hidden" name="products[${productIndex}][is_packaged]" value="0">
                        <input class="form-check-input" type="checkbox" name="products[${productIndex}][is_packaged]" value="1">
                        <label class="form-check-label">بسته‌بندی</label>
                    </div>
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

        const kilnSelect = document.getElementById('kiln_type');
        const dateInput = document.getElementById('date');

        kilnSelect?.addEventListener('change', function() {
            toggleSubtype();
            loadFiringNumber();
        });
        dateInput?.addEventListener('change', loadFiringNumber);
        dateInput?.addEventListener('input', loadFiringNumber);

        toggleSubtype();
        loadFiringNumber();
        addProductRow();
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت پخت شاتل جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shuttle.index') }}">پخت‌های شاتل</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('shuttle.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                           value="{{ old('date', $yesterday ?? '') }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع کوره <span class="text-danger">*</span></label>
                    <select name="kiln_type" id="kiln_type" class="form-select @error('kiln_type') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="kiln_1" {{ old('kiln_type') == 'kiln_1' ? 'selected' : '' }}>کوره ۱</option>
                        <option value="kiln_2" {{ old('kiln_type') == 'kiln_2' ? 'selected' : '' }}>کوره ۲</option>
                        <option value="kiln_3" {{ old('kiln_type') == 'kiln_3' ? 'selected' : '' }}>کوره ۳</option>
                        <option value="packaging" {{ old('kiln_type') == 'packaging' ? 'selected' : '' }}>بسته‌بندی</option>
                    </select>
                    @error('kiln_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3" id="subtype-group" style="display: none;">
                    <label class="form-label">نوع پخت <span class="text-danger">*</span></label>
                    <select name="firing_subtype" id="firing_subtype" class="form-select @error('firing_subtype') is-invalid @enderror">
                        <option value="">انتخاب کنید...</option>
                        <option value="mum" {{ old('firing_subtype') == 'mum' ? 'selected' : '' }}>موم (۹۰۰°)</option>
                        <option value="glaze" {{ old('firing_subtype') == 'glaze' ? 'selected' : '' }}>لعابدار</option>
                    </select>
                    @error('firing_subtype')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">شماره پخت</label>
                    <div class="form-control bg-light fw-bold" id="firing-number-display">—</div>
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">➕ محصولات این پخت</h6>
                    <div id="products-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addProductRow()">
                        <i class="fas fa-plus-circle"></i> افزودن محصول
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('shuttle.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection