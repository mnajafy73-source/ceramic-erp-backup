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
    <h4 class="fw-bold mb-1">ویرایش موجودی اولیه محصول</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('opening-inventories.index') }}">موجودی اول دوره</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('opening-inventories.update', $openingInventory) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب محصول...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $openingInventory->product_id) == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">موجودی اولیه (عدد) <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" 
                           value="{{ old('quantity', $openingInventory->quantity) }}" min="0" required>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">تاریخ</label>
                    <input type="text" name="date" class="form-control datepicker @error('date') is-invalid @enderror" 
                           value="{{ old('date', jdate($openingInventory->date)->format('Y/m/d')) }}">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ویرایش</button>
            <a href="{{ route('opening-inventories.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>
@endsection