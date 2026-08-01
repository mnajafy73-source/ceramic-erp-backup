@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    let productIndex = 0;

    function addProductRow(productId = '', quantity = '', isPackaged = false) {
        const container = document.getElementById('products-container');
        if (!container) return;
        const checked = isPackaged ? 'checked' : '';
        const html = `
            <div class="row g-2 mb-2 product-row" id="product-row-${productIndex}">
                <div class="col-md-4">
                    <select name="products[${productIndex}][product_id]" class="form-select product-select" required>
                        <option value="">انتخاب محصول...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" ${productId == {{ $p->id }} ? 'selected' : ''}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" name="products[${productIndex}][output_quantity]" class="form-control" placeholder="تعداد" step="0.01" value="${quantity}">
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input type="hidden" name="products[${productIndex}][is_packaged]" value="0">
                        <input class="form-check-input" type="checkbox" name="products[${productIndex}][is_packaged]" value="1" ${checked}>
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

        $(`#product-row-${productIndex} .product-select`).select2({
            theme: 'bootstrap-5',
            placeholder: 'جستجوی محصول...',
            allowClear: true,
            language: 'fa'
        });

        productIndex++;
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

    document.addEventListener('DOMContentLoaded', function() {
        $('.product-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'جستجوی محصول...',
            allowClear: true,
            language: 'fa'
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

        const kilnSelect = document.getElementById('kiln_type');
        kilnSelect?.addEventListener('change', toggleSubtype);

        toggleSubtype();

        if (document.querySelectorAll('.product-row').length === 0) {
            addProductRow();
        }
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

                {{-- فیلد شماره پخت حذف شد --}}
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
            <a href="{{ route('shuttle.index') }}" class="btn btn-secondary ms-2">بازگشت</a>
        </form>
    </div>
</div>
@endsection