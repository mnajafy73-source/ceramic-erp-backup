@extends('layouts.app')

@push('styles')
<style>
    .form-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    }
    .form-section .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 2px solid #f1f3f5;
    }
    .form-section .section-header .section-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 15px;
        flex-shrink: 0;
    }
    .form-section .section-header h6 {
        margin: 0;
        font-weight: bold;
        font-size: 15px;
    }
    .form-section .section-header small {
        display: block;
        font-weight: normal;
        color: #6c757d;
        font-size: 12px;
        margin-top: 2px;
    }
    .icon-basic      { background: #0d6efd; }
    .icon-production { background: #fd7e14; }
    .icon-packaging  { background: #198754; }
    .icon-status     { background: #6f42c1; }
    .icon-alias      { background: #20c997; }

    .field-group {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px;
        border-right: 3px solid #dee2e6;
        height: 100%;
    }
    .field-group .field-group-title {
        font-size: 12px;
        font-weight: bold;
        color: #495057;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .field-group.carton-group { border-right-color: #198754; }
    .field-group.layer-group  { border-right-color: #0dcaf0; }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.15rem rgba(13,110,253,0.15);
    }
    .checkbox-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px 16px;
        border: 1px solid #dee2e6;
        height: 100%;
        display: flex;
        align-items: center;
    }
    .checkbox-card .form-check {
        margin: 0;
    }
    .checkbox-card .form-check-label {
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }
    .action-bar {
        background: #fff;
        border-radius: 12px;
        padding: 16px 24px;
        border: 1px solid #e9ecef;
        position: sticky;
        bottom: 12px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت کالای جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">کالاها</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<form action="{{ route('products.store') }}" method="POST">
    @csrf

    {{-- ============================================================== --}}
    {{--  ۱. اطلاعات پایه                                              --}}
    {{-- ============================================================== --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-basic"><i class="fas fa-info-circle"></i></div>
            <div>
                <h6>اطلاعات پایه</h6>
                <small>اطلاعات اصلی و شناسایی کالا</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">کد <span class="text-danger">*</span></label>
                <input type="text" name="code"
                       class="form-control @error('code') is-invalid @enderror"
                       value="{{ old('code') }}" required>
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">نام <span class="text-danger">*</span></label>
                <input type="text" name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">واحد <span class="text-danger">*</span></label>
                <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                    <option value="">انتخاب واحد</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
                @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">وزن (گرم)</label>
                <input type="number" name="weight"
                       class="form-control @error('weight') is-invalid @enderror"
                       value="{{ old('weight') }}" step="0.01" min="0">
                @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{--  ۲. فرآیند تولید                                              --}}
    {{-- ============================================================== --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-production"><i class="fas fa-industry"></i></div>
            <div>
                <h6>فرآیند تولید</h6>
                <small>مشخصات فنی و تولیدی محصول</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label">نوع محصول</label>
                <select name="product_type" class="form-select @error('product_type') is-invalid @enderror">
                    <option value="normal" {{ old('product_type') == 'normal' ? 'selected' : '' }}>معمولی</option>
                    <option value="injection" {{ old('product_type') == 'injection' ? 'selected' : '' }}>تزریق</option>
                </select>
                @error('product_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">فرایند پخت</label>
                <select name="firing_process" class="form-select @error('firing_process') is-invalid @enderror">
                    <option value="tonneli" {{ old('firing_process') == 'tonneli' ? 'selected' : '' }}>تونلی</option>
                    <option value="shuttle" {{ old('firing_process') == 'shuttle' ? 'selected' : '' }}>شاتل</option>
                    <option value="both" {{ old('firing_process') == 'both' ? 'selected' : '' }}>هر دو</option>
                </select>
                @error('firing_process')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">تعداد حفره</label>
                <input type="number" name="cavities"
                       class="form-control @error('cavities') is-invalid @enderror"
                       value="{{ old('cavities', 1) }}" min="1">
                @error('cavities')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label">خوراک پخت تونلی</label>
                <input type="number" name="tonneli_feed_rate"
                       class="form-control @error('tonneli_feed_rate') is-invalid @enderror"
                       value="{{ old('tonneli_feed_rate') }}" min="0">
                @error('tonneli_feed_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-6">
                <label class="form-label">فرمول</label>
                <select name="formula_id" class="form-select @error('formula_id') is-invalid @enderror">
                    <option value="">بدون فرمول</option>
                    @foreach($formulas as $formula)
                        <option value="{{ $formula->id }}" {{ old('formula_id') == $formula->id ? 'selected' : '' }}>
                            {{ $formula->name }}
                        </option>
                    @endforeach
                </select>
                @error('formula_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 col-lg-6">
                <label class="form-label">محصول خام (والد)</label>
                <select name="parent_product_id" class="form-select @error('parent_product_id') is-invalid @enderror">
                    <option value="">بدون والد (خام)</option>
                    @foreach($allProducts as $p)
                        <option value="{{ $p->id }}" {{ old('parent_product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{--  ۳. بسته‌بندی                                                 --}}
    {{-- ============================================================== --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-packaging"><i class="fas fa-box"></i></div>
            <div>
                <h6>بسته‌بندی</h6>
                <small>مشخصات کارتن، لایه، بسته و پالت</small>
            </div>
        </div>

        <div class="row g-3">
            {{-- کارتن --}}
            <div class="col-md-6">
                <div class="field-group carton-group">
                    <div class="field-group-title">
                        <i class="fas fa-box text-success"></i>
                        کارتن
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">کارتن مصرفی</label>
                            <select name="carton_packaging_id"
                                    class="form-select @error('carton_packaging_id') is-invalid @enderror">
                                <option value="">انتخاب کارتن</option>
                                @foreach($packagings as $packaging)
                                    <option value="{{ $packaging->id }}"
                                        {{ old('carton_packaging_id') == $packaging->id ? 'selected' : '' }}>
                                        {{ $packaging->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('carton_packaging_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">تعداد در کارتن</label>
                            <input type="number" name="per_box"
                                   class="form-control @error('per_box') is-invalid @enderror"
                                   value="{{ old('per_box') }}" min="0"
                                   placeholder="مثلاً ۱۲">
                            @error('per_box')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- لایه --}}
            <div class="col-md-6">
                <div class="field-group layer-group">
                    <div class="field-group-title">
                        <i class="fas fa-layer-group text-info"></i>
                        لایه
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">لایه مصرفی</label>
                            <select name="layer_packaging_id"
                                    class="form-select @error('layer_packaging_id') is-invalid @enderror">
                                <option value="">انتخاب لایه</option>
                                @foreach($packagings as $packaging)
                                    <option value="{{ $packaging->id }}"
                                        {{ old('layer_packaging_id') == $packaging->id ? 'selected' : '' }}>
                                        {{ $packaging->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('layer_packaging_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">تعداد لایه در کارتن</label>
                            <input type="number" name="layers_per_box"
                                   class="form-control @error('layers_per_box') is-invalid @enderror"
                                   value="{{ old('layers_per_box') }}" min="0"
                                   placeholder="مثلاً ۳">
                            @error('layers_per_box')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- بسته و پالت --}}
            <div class="col-md-6">
                <label class="form-label">تعداد در بسته</label>
                <input type="number" name="per_pack"
                       class="form-control @error('per_pack') is-invalid @enderror"
                       value="{{ old('per_pack') }}" min="0">
                @error('per_pack')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">تعداد در پالت</label>
                <input type="number" name="per_pallet"
                       class="form-control @error('per_pallet') is-invalid @enderror"
                       value="{{ old('per_pallet') }}" min="0">
                @error('per_pallet')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{--  ۴. وضعیت                                                     --}}
    {{-- ============================================================== --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-status"><i class="fas fa-toggle-on"></i></div>
            <div>
                <h6>وضعیت</h6>
                <small>وضعیت نمایش و تولید کالا</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="checkbox-card">
                    <div class="form-check">
                        <input type="checkbox" name="status" class="form-check-input" id="status" value="1"
                               {{ old('status', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            فعال
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="checkbox-card">
                    <div class="form-check">
                        <input type="checkbox" name="in_production" class="form-check-input" id="in_production" value="1"
                               {{ old('in_production') ? 'checked' : '' }}>
                        <label class="form-check-label" for="in_production">
                            <i class="fas fa-cogs text-primary me-1"></i>
                            در تولید
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{--  ۵. نام‌های مستعار                                             --}}
    {{-- ============================================================== --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-icon icon-alias"><i class="fas fa-tags"></i></div>
            <div>
                <h6>نام‌های مستعار</h6>
                <small>نام‌های دیگر این محصول در فایل اکسل</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12">
                <input type="text" name="aliases"
                       class="form-control @error('aliases') is-invalid @enderror"
                       value="{{ old('aliases') }}"
                       placeholder="مثال: بلسن بتا، بلسن بدون آرم، بلسن جوشا">
                @error('aliases')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    نام‌ها را با کاما (،) یا ویرگول (,) جدا کنید.
                </small>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{--  دکمه‌های اقدام                                                --}}
    {{-- ============================================================== --}}
    <div class="action-bar">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                <i class="fas fa-asterisk text-danger me-1" style="font-size: 8px;"></i>
                فیلدهای اجباری
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-1"></i>
                    انصراف
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>
                    ثبت کالا
                </button>
            </div>
        </div>
    </div>
</form>
@endsection