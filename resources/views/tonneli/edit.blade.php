@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش پخت تونلی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('tonneli.index') }}">تونلی</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('tonneli.update', $tonneli) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', $tonneli->jalali_date) }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>محصول</th>
                            <th>ورودی</th>
                            <th>خروجی</th>
                            <th>بسته‌بندی</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody id="items-container">
                        @forelse($tonneli->items as $index => $item)
                            <tr class="item-row">
                                <td>
                                    <select name="items[{{ $index }}][product_id]" class="form-select" required>
                                        <option value="">انتخاب محصول</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][input_quantity]" class="form-control" placeholder="ورودی" step="1" min="0"
                                           value="{{ $item->input_quantity }}">
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][output_quantity]" class="form-control" placeholder="خروجی" step="1" min="0"
                                           value="{{ $item->output_quantity }}">
                                </td>
                                <td>
                                    <select name="items[{{ $index }}][is_packaged]" class="form-select">
                                        <option value="0" {{ !$item->is_packaged ? 'selected' : '' }}>خیر</option>
                                        <option value="1" {{ $item->is_packaged ? 'selected' : '' }}>بله</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row">✖</button>
                                </td>
                            </tr>
                        @empty
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
                                    <input type="number" name="items[0][input_quantity]" class="form-control" placeholder="ورودی" step="1" min="0">
                                </td>
                                <td>
                                    <input type="number" name="items[0][output_quantity]" class="form-control" placeholder="خروجی" step="1" min="0">
                                </td>
                                <td>
                                    <select name="items[0][is_packaged]" class="form-select">
                                        <option value="0">خیر</option>
                                        <option value="1">بله</option>
                                    </select>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger remove-row" style="display:none;">✖</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-sm btn-secondary" id="add-row">
                    <i class="fas fa-plus-circle"></i> افزودن ردیف
                </button>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">به‌روزرسانی</button>
                <a href="{{ route('tonneli.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let rowIndex = {{ $tonneli->items->count() ?: 1 }};

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
                    <input type="number" name="items[${rowIndex}][input_quantity]" class="form-control" placeholder="ورودی" step="1" min="0">
                </td>
                <td>
                    <input type="number" name="items[${rowIndex}][output_quantity]" class="form-control" placeholder="خروجی" step="1" min="0">
                </td>
                <td>
                    <select name="items[${rowIndex}][is_packaged]" class="form-select">
                        <option value="0">خیر</option>
                        <option value="1">بله</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger remove-row">✖</button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        updateRemoveButtons();
    });

    document.getElementById('items-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
                updateRemoveButtons();
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

    document.addEventListener('DOMContentLoaded', function() {
        updateRemoveButtons();
    });
</script>
@endpush
@endsection