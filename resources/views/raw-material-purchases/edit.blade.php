@extends('layouts.app')

@push('scripts')
<script>
    $(document).ready(function() {
        $('.datepicker').persianDatepicker({
            format: 'YYYY/MM/DD',
            initialValue: true,
            autoClose: true,
            todayButton: true,
            initialValueType: 'persian',
            observer: true,
        });
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش خرید مواد اولیه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('raw-material-purchases.index') }}">خرید مواد</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('raw-material-purchases.update', $rawMaterialPurchase) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ خرید <span class="text-danger">*</span></label>
                    <input type="text" name="purchase_date" class="form-control datepicker @error('purchase_date') is-invalid @enderror" 
                           value="{{ old('purchase_date', jdate($rawMaterialPurchase->purchase_date)->format('Y/m/d')) }}" required>
                    @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تأمین‌کننده</label>
                    <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $rawMaterialPurchase->supplier) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">هزینه حمل‌ونقل (ریال)</label>
                    <input type="text" name="total_transport_cost" class="form-control format-number" value="{{ old('total_transport_cost', number_format($rawMaterialPurchase->total_transport_cost)) }}">
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label">مواد خریداری‌شده <span class="text-danger">*</span></label>
                <div id="items-container">
                    @foreach($rawMaterialPurchase->items as $index => $item)
                        @php
                            // تبدیل از گرم به واحد انتخابی
                            $displayQuantity = 0;
                            if ($item->unit === 'ton') {
                                $displayQuantity = $item->quantity / 1000000;
                            } else {
                                $displayQuantity = $item->quantity / 1000;
                            }
                            $formattedQty = rtrim(rtrim(number_format($displayQuantity, 3, '.', ''), '0'), '.');
                            if ($formattedQty === '') $formattedQty = '0';
                        @endphp
                        <div class="item-row row g-2 mb-2">
                            <div class="col-md-4">
                                <select name="items[{{ $index }}][raw_material_id]" class="form-select" required>
                                    <option value="">انتخاب ماده...</option>
                                    @foreach($materials as $material)
                                        <option value="{{ $material->id }}" {{ $item->raw_material_id == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="items[{{ $index }}][quantity]" value="{{ $formattedQty }}" class="form-control format-number" required>
                            </div>
                            <div class="col-md-2">
                                <select name="items[{{ $index }}][unit]" class="form-select" required>
                                    <option value="kg" {{ $item->unit == 'kg' ? 'selected' : '' }}>کیلوگرم</option>
                                    <option value="ton" {{ $item->unit == 'ton' ? 'selected' : '' }}>تن</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="items[{{ $index }}][total_price]" value="{{ number_format($item->total_price) }}" class="form-control format-number" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger remove-item w-100">-</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-item" class="btn btn-success mt-2">
                    <i class="fas fa-plus me-1"></i> افزودن ماده
                </button>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> بروزرسانی</button>
            <a href="{{ route('raw-material-purchases.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>

<script>
    let itemCount = {{ $rawMaterialPurchase->items->count() }};
    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const newRow = document.createElement('div');
        newRow.className = 'item-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-4">
                <select name="items[${itemCount}][raw_material_id]" class="form-select" required>
                    <option value="">انتخاب ماده...</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="items[${itemCount}][quantity]" placeholder="مقدار" class="form-control format-number" required>
            </div>
            <div class="col-md-2">
                <select name="items[${itemCount}][unit]" class="form-select" required>
                    <option value="kg">کیلوگرم</option>
                    <option value="ton">تن</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="items[${itemCount}][total_price]" placeholder="قیمت کل (ریال)" class="form-control format-number" required>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger remove-item w-100">-</button>
            </div>
        `;
        container.appendChild(newRow);
        itemCount++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('حداقل یک ماده باید وجود داشته باشد.');
            }
        }
    });
</script>
@endsection