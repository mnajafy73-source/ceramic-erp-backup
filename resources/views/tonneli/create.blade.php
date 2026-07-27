@extends('layouts.app')

@push('scripts')
<script>
    $(function() {
        try {
            $('#date').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                observer: true,
                calendar: { persian: { locale: 'fa' } }
            });
        } catch(e) {}
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت پخت تونلی جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('tonneli.index') }}">پخت‌های تونلی</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('tonneli.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" 
                           value="{{ old('date', $yesterday ?? '') }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">ورودی (بارگذاری)</label>
                    <input type="number" name="input_quantity" class="form-control @error('input_quantity') is-invalid @enderror" 
                           value="{{ old('input_quantity', 0) }}" min="0" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">خروجی (بسته‌بندی‌شده)</label>
                    <input type="number" name="output_quantity" class="form-control @error('output_quantity') is-invalid @enderror" 
                           value="{{ old('output_quantity', 0) }}" min="0" step="0.01">
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_packaged" id="is_packaged" value="1" {{ old('is_packaged') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_packaged">بسته‌بندی شده</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('tonneli.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection