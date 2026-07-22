@extends('layouts.app')

@push('scripts')
<script>
    $(function() {
        $('#date').persianDatepicker({ format: 'YYYY/MM/DD', autoClose: true, initialValue: false, observer: true, calendar: { persian: { locale: 'fa' } } });
        toggleSubtype();
        $('#kiln_type').on('change', toggleSubtype);
    });
    function toggleSubtype() {
        if ($('#kiln_type').val() === 'kiln_3') {
            $('#subtype-group').show();
        } else {
            $('#subtype-group').hide();
            $('#firing_subtype').val('');
        }
    }
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
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $yesterday ?? '') }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
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
                <div class="col-md-6 mb-3" id="subtype-group" style="display: none;">
                    <label class="form-label">نوع پخت</label>
                    <select name="firing_subtype" id="firing_subtype" class="form-select @error('firing_subtype') is-invalid @enderror">
                        <option value="">انتخاب کنید...</option>
                        <option value="mum" {{ old('firing_subtype') == 'mum' ? 'selected' : '' }}>موم (۹۰۰°)</option>
                        <option value="glaze" {{ old('firing_subtype') == 'glaze' ? 'selected' : '' }}>لعابدار</option>
                    </select>
                    @error('firing_subtype')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">خروجی</label>
                    <input type="number" name="output_quantity" class="form-control @error('output_quantity') is-invalid @enderror" value="{{ old('output_quantity') }}" step="0.01">
                    <div class="form-text">خالی = در انتظار بسته‌بندی</div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="is_packaged" id="is_packaged" value="1" {{ old('is_packaged') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_packaged">بسته‌بندی شده</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('shuttle.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection