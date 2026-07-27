@extends('layouts.app')

@push('scripts')
<script>
    let productIndex = {{ $items->count() }};
    function addProductRow(productId = '', qty = '', packaged = false) {
        const container = document.getElementById('products-container');
        if (!container) return;
        const checked = packaged ? 'checked' : '';
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
                    <input type="number" name="products[${productIndex}][output_quantity]" class="form-control" value="${qty}" placeholder="تعداد" step="0.01">
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
        productIndex++;
    }

    document.addEventListener('DOMContentLoaded', function() {
        @foreach($items as $i => $item)
            addProductRow({{ $item->product_id }}, '{{ $item->output_quantity }}', {{ $item->is_packaged ? 'true' : 'false' }});
        @endforeach
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش پخت شاتل شماره {{ $firingNumber }}</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shuttle.index') }}">پخت‌های شاتل</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('shuttle.update', ['firingNumber' => $firingNumber, 'date' => $date, 'kiln_type' => $kilnType]) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="date" value="{{ old('date', $jalaliDate) }}">
            <input type="hidden" name="kiln_type" value="{{ $kilnType }}">
            @if($items->first()->firing_subtype)
                <input type="hidden" name="firing_subtype" value="{{ $items->first()->firing_subtype }}">
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاریخ</label>
                    <input type="text" class="form-control" value="{{ $jalaliDate }}" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع کوره</label>
                    @php
                        $kilnLabels = ['kiln_1' => 'کوره ۱', 'kiln_2' => 'کوره ۲', 'kiln_3' => 'کوره ۳', 'packaging' => 'بسته‌بندی'];
                    @endphp
                    <input type="text" class="form-control" value="{{ $kilnLabels[$kilnType] ?? $kilnType }}" disabled>
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

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> بروزرسانی</button>
            <a href="{{ route('shuttle.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection