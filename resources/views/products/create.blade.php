@extends('layouts.app')

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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <!-- کد -->
                <div class="col-md-3">
                    <label class="form-label">کد <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                           value="{{ old('code') }}" required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- نام -->
                <div class="col-md-3">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- واحد -->
                <div class="col-md-3">
                    <label class="form-label">واحد <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                        <option value="">انتخاب واحد</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- وزن -->
                <div class="col-md-3">
                    <label class="form-label">وزن (گرم)</label>
                    <input type="number" name="weight" class="form-control @error('weight') is-invalid @enderror"
                           value="{{ old('weight') }}" step="0.01" min="0">
                    @error('weight')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- فرمول -->
                <div class="col-md-3">
                    <label class="form-label">فرمول</label>
                    <select name="formula_id" class="form-select @error('formula_id') is-invalid @enderror">
                        <option value="">بدون فرمول</option>
                        @foreach($formulas as $formula)
                            <option value="{{ $formula->id }}" {{ old('formula_id') == $formula->id ? 'selected' : '' }}>
                                {{ $formula->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('formula_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد در کارتن -->
                <div class="col-md-3">
                    <label class="form-label">تعداد در کارتن</label>
                    <input type="number" name="per_box" class="form-control @error('per_box') is-invalid @enderror"
                           value="{{ old('per_box') }}" min="0">
                    @error('per_box')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد لایه در کارتن -->
                <div class="col-md-3">
                    <label class="form-label">تعداد لایه در کارتن</label>
                    <input type="number" name="layers_per_box" class="form-control @error('layers_per_box') is-invalid @enderror"
                           value="{{ old('layers_per_box') }}" min="0">
                    @error('layers_per_box')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- کارتن -->
                <div class="col-md-3">
                    <label class="form-label">کارتن مصرفی</label>
                    <select name="carton_packaging_id" class="form-select @error('carton_packaging_id') is-invalid @enderror">
                        <option value="">انتخاب کارتن</option>
                        @foreach($packagings as $packaging)
                            <option value="{{ $packaging->id }}" {{ old('carton_packaging_id') == $packaging->id ? 'selected' : '' }}>
                                {{ $packaging->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('carton_packaging_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- لایه -->
                <div class="col-md-3">
                    <label class="form-label">لایه مصرفی</label>
                    <select name="layer_packaging_id" class="form-select @error('layer_packaging_id') is-invalid @enderror">
                        <option value="">انتخاب لایه</option>
                        @foreach($packagings as $packaging)
                            <option value="{{ $packaging->id }}" {{ old('layer_packaging_id') == $packaging->id ? 'selected' : '' }}>
                                {{ $packaging->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('layer_packaging_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- سرعت خوراک تونلی -->
                <div class="col-md-3">
                    <label class="form-label">خوراک پخت تونلی</label>
                    <input type="number" name="tonneli_feed_rate" class="form-control @error('tonneli_feed_rate') is-invalid @enderror"
                           value="{{ old('tonneli_feed_rate') }}" min="0">
                    @error('tonneli_feed_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- حفره -->
                <div class="col-md-3">
                    <label class="form-label">تعداد حفره</label>
                    <input type="number" name="cavities" class="form-control @error('cavities') is-invalid @enderror"
                           value="{{ old('cavities', 1) }}" min="1">
                    @error('cavities')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد در بسته -->
                <div class="col-md-3">
                    <label class="form-label">تعداد در بسته</label>
                    <input type="number" name="per_pack" class="form-control @error('per_pack') is-invalid @enderror"
                           value="{{ old('per_pack') }}" min="0">
                    @error('per_pack')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد در پالت -->
                <div class="col-md-3">
                    <label class="form-label">تعداد در پالت</label>
                    <input type="number" name="per_pallet" class="form-control @error('per_pallet') is-invalid @enderror"
                           value="{{ old('per_pallet') }}" min="0">
                    @error('per_pallet')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- وضعیت -->
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="status" class="form-check-input" id="status" value="1"
                               {{ old('status', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">فعال</label>
                    </div>
                </div>

                <!-- در تولید -->
                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="in_production" class="form-check-input" id="in_production" value="1"
                               {{ old('in_production') ? 'checked' : '' }}>
                        <label class="form-check-label" for="in_production">در تولید</label>
                    </div>
                </div>

                <!-- فرایند پخت -->
                <div class="col-md-3">
                    <label class="form-label">فرایند پخت</label>
                    <select name="firing_process" class="form-select @error('firing_process') is-invalid @enderror">
                        <option value="tonneli" {{ old('firing_process') == 'tonneli' ? 'selected' : '' }}>تونلی</option>
                        <option value="shuttle" {{ old('firing_process') == 'shuttle' ? 'selected' : '' }}>شاتل</option>
                        <option value="both" {{ old('firing_process') == 'both' ? 'selected' : '' }}>هر دو</option>
                    </select>
                    @error('firing_process')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- ===== والد (محصول خام) ===== -->
                <div class="col-md-3">
                    <label class="form-label">محصول خام (والد)</label>
                    <select name="parent_product_id" class="form-select @error('parent_product_id') is-invalid @enderror">
                        <option value="">بدون والد (خام)</option>
                        @foreach($allProducts as $p)
                            <option value="{{ $p->id }}" {{ old('parent_product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- ========== بخش نام‌های مستعار ========== -->
            <div class="row mt-4">
                <div class="col-12">
                    <hr>
                    <h6 class="fw-bold">نام‌های مستعار (نام‌های دیگر این محصول در فایل اکسل)</h6>
                    <p class="text-muted small">
                        نام‌ها را با کاما (،) یا ویرگول (,) جدا کنید.
                        مثال: بلسن بتا، بلسن بدون آرم، بلسن جوشا
                    </p>
                    <input type="text" name="aliases" class="form-control @error('aliases') is-invalid @enderror"
                           value="{{ old('aliases') }}"
                           placeholder="مثال: بلسن بتا، بلسن بدون آرم">
                    @error('aliases')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">ثبت کالا</button>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>
@endsection