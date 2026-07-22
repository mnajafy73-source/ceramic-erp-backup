@extends('layouts.app')

@section('title', 'ویرایش کالا')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش کالا: {{ $product->name }} (کد: {{ $product->code }})</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">کالاها</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">کد کالا</label>
                    <input type="text" class="form-control-plaintext" readonly value="{{ $product->code }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">نام کالا <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="unit_id" class="form-label">واحد <span class="text-danger">*</span></label>
                    <select name="unit_id" id="unit_id" class="form-select @error('unit_id') is-invalid @enderror" required>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="initial_stock" class="form-label">موجودی اولیه</label>
                    <input type="number" name="initial_stock" id="initial_stock" class="form-control @error('initial_stock') is-invalid @enderror" value="{{ old('initial_stock', $product->initial_stock) }}" min="0">
                    @error('initial_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="kiln_type" class="form-label">نوع کوره <span class="text-danger">*</span></label>
                    <select name="kiln_type" id="kiln_type" class="form-select @error('kiln_type') is-invalid @enderror" required>
                        <option value="tonneli" {{ old('kiln_type', $product->kiln_type) == 'tonneli' ? 'selected' : '' }}>تونلی</option>
                        <option value="shuttle" {{ old('kiln_type', $product->kiln_type) == 'shuttle' ? 'selected' : '' }}>شاتل</option>
                        <option value="both" {{ old('kiln_type', $product->kiln_type) == 'both' ? 'selected' : '' }}>هر دو</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="firing_process" class="form-label">فرآیند پخت <span class="text-danger">*</span></label>
                    <select name="firing_process" id="firing_process" class="form-select @error('firing_process') is-invalid @enderror" required>
                        <option value="standard" {{ old('firing_process', $product->firing_process) == 'standard' ? 'selected' : '' }}>پخت معمولی</option>
                        <option value="multistage" {{ old('firing_process', $product->firing_process) == 'multistage' ? 'selected' : '' }}>پخت چندمرحله‌ای</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="tonneli_feed_rate" class="form-label">خوراک تونلی (عدد/ساعت)</label>
                    <input type="number" name="tonneli_feed_rate" id="tonneli_feed_rate" class="form-control" value="{{ old('tonneli_feed_rate', $product->tonneli_feed_rate) }}" min="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cavities" class="form-label">حفره</label>
                    <input type="number" name="cavities" id="cavities" class="form-control" value="{{ old('cavities', $product->cavities) }}" min="1">
                </div>
                <div class="col-md-4 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', $product->status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">فعال</label>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="per_box" class="form-label">تعداد در کارتن</label>
                    <input type="number" name="per_box" id="per_box" class="form-control" value="{{ old('per_box', $product->per_box) }}" min="0">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="per_pack" class="form-label">تعداد در بسته</label>
                    <input type="number" name="per_pack" id="per_pack" class="form-control" value="{{ old('per_pack', $product->per_pack) }}" min="0">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="per_pallet" class="form-label">تعداد در پالت</label>
                    <input type="number" name="per_pallet" id="per_pallet" class="form-control" value="{{ old('per_pallet', $product->per_pallet) }}" min="0">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="box_type" class="form-label">نوع کارتن</label>
                    <input type="text" name="box_type" id="box_type" class="form-control" value="{{ old('box_type', $product->box_type) }}">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="layers_per_box" class="form-label">تعداد لایه در کارتن</label>
                    <input type="number" name="layers_per_box" id="layers_per_box" class="form-control" value="{{ old('layers_per_box', $product->layers_per_box) }}" min="0">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="in_production" id="in_production" value="1" {{ old('in_production', $product->in_production) ? 'checked' : '' }}>
                        <label class="form-check-label" for="in_production">در حال تولید</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt me-1"></i> بروزرسانی</button>
            <a href="{{ route('products.index') }}" class="btn btn-secondary me-2">انصراف</a>
        </form>
    </div>
</div>
@endsection