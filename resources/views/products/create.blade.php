@extends('layouts.app')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // فیلد خوراک همیشه نمایش داده می‌شود
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">تعریف کالای جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">کالاها</a></li>
            <li class="breadcrumb-item active">تعریف جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">کد کالا <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                           value="{{ old('code') }}" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نام کالا <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">واحد <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تعداد حفره قالب</label>
                    <input type="number" name="cavities" class="form-control" value="{{ old('cavities', 1) }}" min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">خوراک پخت کوره تونلی (تعداد در ساعت)</label>
                    <input type="number" name="tonneli_feed_rate" class="form-control" value="{{ old('tonneli_feed_rate') }}" min="0">
                    <small class="text-muted">اختیاری - در صورت وارد کردن، در داشبورد نمایش داده می‌شود.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تعداد در کارتن</label>
                    <input type="number" name="per_box" class="form-control" value="{{ old('per_box') }}" min="0">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تعداد در بسته</label>
                    <input type="number" name="per_pack" class="form-control" value="{{ old('per_pack') }}" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تعداد در پالت</label>
                    <input type="number" name="per_pallet" class="form-control" value="{{ old('per_pallet') }}" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">نوع کارتن</label>
                    <input type="text" name="box_type" class="form-control" value="{{ old('box_type') }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">تعداد لایه در کارتن</label>
                    <input type="number" name="layers_per_box" class="form-control" value="{{ old('layers_per_box') }}" min="0">
                </div>
                
                <!-- فیلدهای جدید: وزن و فرمول -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">وزن (گرم)</label>
                    <input type="number" name="weight" class="form-control @error('weight') is-invalid @enderror" 
                           value="{{ old('weight') }}" step="0.01" min="0">
                    @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">وزن هر عدد محصول به گرم</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">فرمول</label>
                    <select name="formula_id" class="form-select @error('formula_id') is-invalid @enderror">
                        <option value="">انتخاب فرمول...</option>
                        @foreach($formulas as $formula)
                            <option value="{{ $formula->id }}" {{ old('formula_id') == $formula->id ? 'selected' : '' }}>{{ $formula->name }}</option>
                        @endforeach
                    </select>
                    @error('formula_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">فرمول مواد مصرفی این کالا</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">فعال</label>
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="in_production" id="in_production" value="1" {{ old('in_production') ? 'checked' : '' }}>
                        <label class="form-check-label" for="in_production">در حال تولید</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection