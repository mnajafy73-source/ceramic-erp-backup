@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش فرمول</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('formulas.index') }}">فرمول‌ها</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('formulas.update', $formula) }}" method="POST" id="formula-form">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">نام فرمول <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                       value="{{ old('name', $formula->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">مواد تشکیل‌دهنده <span class="text-danger">*</span></label>
                <div id="items-container">
                    @foreach($formula->items as $index => $item)
                    <div class="item-row row g-2 mb-2">
                        <div class="col-md-5">
                            <select name="items[{{ $index }}][raw_material_id]" class="form-select" required>
                                <option value="">انتخاب ماده...</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}" {{ $item->raw_material_id == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input type="number" name="items[{{ $index }}][percentage]" value="{{ $item->percentage }}" placeholder="درصد" class="form-control" required step="0.01" min="0" max="100">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger remove-item w-100">-</button>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-item" class="btn btn-success mt-2">
                    <i class="fas fa-plus me-1"></i> افزودن ماده
                </button>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ویرایش</button>
            <a href="{{ route('formulas.index') }}" class="btn btn-secondary ms-2">انصراف</a>
        </form>
    </div>
</div>

<script>
    let itemCount = {{ $formula->items->count() }};
    document.getElementById('add-item').addEventListener('click', function() {
        const container = document.getElementById('items-container');
        const newRow = document.createElement('div');
        newRow.className = 'item-row row g-2 mb-2';
        newRow.innerHTML = `
            <div class="col-md-5">
                <select name="items[${itemCount}][raw_material_id]" class="form-select" required>
                    <option value="">انتخاب ماده...</option>
                    @foreach($materials as $material)
                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <input type="number" name="items[${itemCount}][percentage]" placeholder="درصد" class="form-control" required step="0.01" min="0" max="100">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-item w-100">-</button>
            </div>
        `;
        container.appendChild(newRow);
        itemCount++;
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            const row = e.target.closest('.item-row');
            if (document.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('حداقل یک ماده باید وجود داشته باشد.');
            }
        }
    });
</script>
@endsection