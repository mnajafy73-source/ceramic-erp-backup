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
    <h4 class="fw-bold mb-1">ویرایش خرید کارتن و لایه</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('packaging-purchases.index') }}">خرید کارتن و لایه</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('packaging-purchases.update', $packagingPurchase) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ خرید <span class="text-danger">*</span></label>
                    <input type="text" name="purchase_date" class="form-control datepicker @error('purchase_date') is-invalid @enderror" 
                           value="{{ old('purchase_date', jdate($packagingPurchase->purchase_date)->format('Y/m/d')) }}" required>
                    @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تأمین‌کننده</label>
                    <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $packagingPurchase->supplier) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">هزینه حمل‌ونقل (ریال)</label>
                    <input type="text" name="total_transport_cost" class="form-control format-number" value="{{ old('total_transport_cost', number_format($packagingPurchase->total_transport_cost)) }}">
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label">اقلام خریداری‌شده <span class="text-danger">*</span></label>
                <div id="items-container">
                    @foreach($packagingPurchase->items as $index => $item)
                    <div class="item-row row g-2 mb-2">
                        <div class="col-md-4">
                            <select name="items[{{ $index }}][packaging_id]" class="form-select" required>
                                <option value="">انتخاب کارتن/لایه...</option>
                                @foreach($packagings as $packaging)
                                    <option value="{{ $packaging->id }}" {{ $item->packaging_id == $packaging->id ? 'selected' : '' }}>
                                        {{ $packaging->name }} ({{ $packaging->type == 'carton' ? 'کارتن' : 'لایه' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="items[{{ $index }}][quantity]" value="{{ number_format($item->quantity) }}" placeholder="تعداد" class="form-control format-number" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="items[{{ $index }}][total_price]" value="{{ number_format($item->total_price) }}" placeholder="قیمت کل (ریال)" class="form-control format-number" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-item w-100">-</button>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-item" class="btn btn-success mt-2">
                    <i class="fas fa-plus me-1"></i> افزودن قلم
                </button>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> بروزرسانی</button>
            <a href="{{ route('packaging-purchases.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>

<script>
    let itemCount = {{ $packagingPurchase->items->count() }};
    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const newRow = document.createElement('div');
        newRow.className = 'item-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-4">
                <select name="items[${itemCount}][packaging_id]" class="form-select" required>
                    <option value="">انتخاب کارتن/لایه...</option>
                    @foreach($packagings as $packaging)
                        <option value="{{ $packaging->id }}">
                            {{ $packaging->name }} ({{ $packaging->type == 'carton' ? 'کارتن' : 'لایه' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="items[${itemCount}][quantity]" placeholder="تعداد" class="form-control format-number" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="items[${itemCount}][total_price]" placeholder="قیمت کل (ریال)" class="form-control format-number" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item w-100">-</button>
            </div>
        `;
        container.appendChild(newRow);
        itemCount++;

        if (typeof window.applyFormatToNewInputs === 'function') {
            window.applyFormatToNewInputs(container);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('حداقل یک قلم باید وجود داشته باشد.');
            }
        }
    });
</script>
@endsection