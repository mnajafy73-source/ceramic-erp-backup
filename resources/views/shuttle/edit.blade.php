@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش پخت شاتل</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('shuttle.index') }}">کوره شاتل</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('shuttle.update', $firing->firing_number) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <!-- تاریخ -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', $firing->jalali_date) }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- کوره -->
                <div class="col-md-3">
                    <label class="form-label">کوره <span class="text-danger">*</span></label>
                    <select name="kiln_number" class="form-select @error('kiln_number') is-invalid @enderror" required>
                        <option value="">انتخاب کوره</option>
                        <option value="1" {{ old('kiln_number', $firing->kiln_number) == '1' ? 'selected' : '' }}>کوره ۱</option>
                        <option value="2" {{ old('kiln_number', $firing->kiln_number) == '2' ? 'selected' : '' }}>کوره ۲</option>
                        <option value="3" {{ old('kiln_number', $firing->kiln_number) == '3' ? 'selected' : '' }}>کوره ۳</option>
                        <option value="4" {{ old('kiln_number', $firing->kiln_number) == '4' ? 'selected' : '' }}>کوره ۴</option>
                        <option value="packaging" {{ old('kiln_number', $firing->kiln_number) == 'packaging' ? 'selected' : '' }}>بسته‌بندی</option>
                    </select>
                    @error('kiln_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- نوع پخت -->
                <div class="col-md-3">
                    <label class="form-label">نوع پخت <span class="text-danger">*</span></label>
                    <select name="firing_type" class="form-select @error('firing_type') is-invalid @enderror" required>
                        <option value="">انتخاب نوع پخت</option>
                        <option value="معمولی" {{ old('firing_type', $firing->firing_type) == 'معمولی' ? 'selected' : '' }}>معمولی</option>
                        <option value="1300" {{ old('firing_type', $firing->firing_type) == '1300' ? 'selected' : '' }}>۱۳۰۰</option>
                        <option value="لعابدار" {{ old('firing_type', $firing->firing_type) == 'لعابدار' ? 'selected' : '' }}>لعابدار</option>
                        <option value="موم" {{ old('firing_type', $firing->firing_type) == 'موم' ? 'selected' : '' }}>موم</option>
                    </select>
                    @error('firing_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- محصول -->
                <div class="col-md-3">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب محصول</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $firing->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد کل -->
                <div class="col-md-3">
                    <label class="form-label">تعداد کل <span class="text-danger">*</span></label>
                    <input type="number" name="total_quantity" class="form-control @error('total_quantity') is-invalid @enderror"
                           value="{{ old('total_quantity', $firing->total_quantity ?? 0) }}" step="1" min="0" required>
                    @error('total_quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد اصلی -->
                <div class="col-md-3">
                    <label class="form-label">تعداد اصلی (خروجی سالم) <span class="text-danger">*</span></label>
                    <input type="number" name="main_quantity" class="form-control @error('main_quantity') is-invalid @enderror"
                           value="{{ old('main_quantity', $firing->output_quantity) }}" step="1" min="0" required>
                    @error('main_quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- ضایعات -->
                <div class="col-md-3">
                    <label class="form-label">ضایعات <span class="text-danger">*</span></label>
                    <input type="number" name="waste" class="form-control @error('waste') is-invalid @enderror"
                           value="{{ old('waste', 0) }}" step="1" min="0" required>
                    @error('waste')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- بسته‌بندی -->
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_packaged" class="form-check-input" id="is_packaged" value="1"
                               {{ old('is_packaged', $firing->is_packaged) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_packaged">بسته‌بندی شده</label>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">به‌روزرسانی</button>
                <a href="{{ route('shuttle.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
@endsection