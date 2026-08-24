@extends('layouts.app')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ویرایش تولید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productions.index') }}">تولید</a></li>
            <li class="breadcrumb-item active">ویرایش</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('productions.update', $production) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <!-- تاریخ -->
                <div class="col-md-4">
                    <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" class="form-control @error('date') is-invalid @enderror"
                           value="{{ old('date', $production->jalali_date) }}" required>
                    @error('date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- اپراتور -->
                <div class="col-md-4">
                    <label class="form-label">اپراتور <span class="text-danger">*</span></label>
                    <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror" required>
                        <option value="">انتخاب اپراتور</option>
                        @foreach($operators as $operator)
                            <option value="{{ $operator->id }}" {{ old('operator_id', $production->operator_id) == $operator->id ? 'selected' : '' }}>
                                {{ $operator->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('operator_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- پرس -->
                <div class="col-md-4">
                    <label class="form-label">پرس</label>
                    <select name="press_id" class="form-select @error('press_id') is-invalid @enderror">
                        <option value="">بدون پرس</option>
                        @foreach($presses as $press)
                            <option value="{{ $press->id }}" {{ old('press_id', $production->press_id) == $press->id ? 'selected' : '' }}>
                                {{ $press->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('press_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- محصول -->
                <div class="col-md-4">
                    <label class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب محصول</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id', $production->product_id) == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- عملیات (فارسی) -->
                <div class="col-md-4">
                    <label class="form-label">عملیات <span class="text-danger">*</span></label>
                    <select name="stage" class="form-select @error('stage') is-invalid @enderror" required>
                        <option value="">انتخاب عملیات</option>
                        <option value="تولید" {{ old('stage', $production->stage) == 'تولید' ? 'selected' : '' }}>تولید</option>
                        <option value="پرداخت" {{ old('stage', $production->stage) == 'پرداخت' ? 'selected' : '' }}>پرداخت</option>
                        <option value="بسته‌بندی" {{ old('stage', $production->stage) == 'بسته‌بندی' ? 'selected' : '' }}>بسته‌بندی</option>
                    </select>
                    @error('stage')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- تعداد -->
                <div class="col-md-4">
                    <label class="form-label">تعداد <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror"
                           value="{{ old('quantity', $production->quantity) }}" required min="1">
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- زمان (ساعت) -->
                <div class="col-md-4">
                    <label class="form-label">زمان (ساعت)</label>
                    <input type="number" name="time_hours" class="form-control @error('time_hours') is-invalid @enderror"
                           value="{{ old('time_hours', $production->time_hours) }}" step="0.1" min="0">
                    @error('time_hours')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- یادداشت -->
                <div class="col-12">
                    <label class="form-label">یادداشت</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $production->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- توقف‌ها (فارسی) -->
                <div class="col-12 mt-3">
                    <hr>
                    <h6 class="fw-bold">توقف‌ها (اختیاری)</h6>
                    <div id="stops-container">
                        @if($production->stops->count())
                            @foreach($production->stops as $index => $stop)
                                <div class="row g-2 stop-row mt-2">
                                    <div class="col-md-5">
                                        <select name="stop_types[]" class="form-select">
                                            <option value="خرابی ماشین" {{ $stop->type == 'خرابی ماشین' ? 'selected' : '' }}>خرابی ماشین</option>
                                            <option value="تعویض قالب" {{ $stop->type == 'تعویض قالب' ? 'selected' : '' }}>تعویض قالب</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0" value="{{ $stop->hours }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-stop">حذف</button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="row g-2 stop-row">
                                <div class="col-md-5">
                                    <select name="stop_types[]" class="form-select">
                                        <option value="خرابی ماشین">خرابی ماشین</option>
                                        <option value="تعویض قالب">تعویض قالب</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-stop" style="display:none;">حذف</button>
                                </div>
                            </div>
                        @endif
                    </div>
                    <button type="button" id="add-stop" class="btn btn-sm btn-secondary mt-2">➕ افزودن توقف</button>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">به‌روزرسانی</button>
                <a href="{{ route('productions.index') }}" class="btn btn-secondary">انصراف</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let stopIndex = {{ $production->stops->count() ?: 1 }};

        document.getElementById('add-stop').addEventListener('click', function() {
            const container = document.getElementById('stops-container');
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 stop-row mt-2';
            newRow.innerHTML = `
                <div class="col-md-5">
                    <select name="stop_types[]" class="form-select">
                        <option value="خرابی ماشین">خرابی ماشین</option>
                        <option value="تعویض قالب">تعویض قالب</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="number" name="stop_hours[]" class="form-control" placeholder="ساعت" step="0.1" min="0">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-stop">حذف</button>
                </div>
            `;
            container.appendChild(newRow);
            stopIndex++;
        });

        document.getElementById('stops-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-stop')) {
                const row = e.target.closest('.stop-row');
                if (document.querySelectorAll('.stop-row').length > 1) {
                    row.remove();
                } else {
                    alert('حداقل یک ردیف توقف باید باقی بماند.');
                }
            }
        });
    });
</script>
@endpush

@endsection