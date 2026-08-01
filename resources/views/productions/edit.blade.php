@extends('layouts.app')

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            if (typeof $ !== 'undefined' && $.fn.persianDatepicker) {
                $('#date').persianDatepicker({
                    format: 'YYYY/MM/DD',
                    autoClose: true,
                    initialValue: false,
                    observer: true,
                    calendar: { persian: { locale: 'fa' } }
                });
            }
        } catch (e) {}

        // نمایش/مخفی کردن پرس بر اساس عملیات
        function togglePress() {
            const stage = document.getElementById('stage').value;
            const pressGroup = document.getElementById('press-group');
            if (stage === 'production') {
                pressGroup.style.display = 'block';
                document.getElementById('press_id').setAttribute('required', 'required');
            } else {
                pressGroup.style.display = 'none';
                document.getElementById('press_id').removeAttribute('required');
            }
        }

        document.getElementById('stage').addEventListener('change', togglePress);
        togglePress();
    });
</script>
@endpush

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
        <form action="{{ route('productions.update', $production) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label small">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control form-control-sm @error('date') is-invalid @enderror"
                           value="{{ old('date', $production->jalali_date) }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label small">اپراتور <span class="text-danger">*</span></label>
                    <select name="operator_id" class="form-select form-select-sm @error('operator_id') is-invalid @enderror" required>
                        @foreach($operators as $op)
                            <option value="{{ $op->id }}" {{ old('operator_id', $production->operator_id) == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                        @endforeach
                    </select>
                    @error('operator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label small">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select form-select-sm @error('product_id') is-invalid @enderror" required>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id', $production->product_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label small">عملیات <span class="text-danger">*</span></label>
                    <select name="stage" id="stage" class="form-select form-select-sm @error('stage') is-invalid @enderror" required>
                        <option value="production" {{ old('stage', $production->stage) == 'production' ? 'selected' : '' }}>تولید</option>
                        <option value="payment" {{ old('stage', $production->stage) == 'payment' ? 'selected' : '' }}>پرداخت</option>
                        <option value="packaging" {{ old('stage', $production->stage) == 'packaging' ? 'selected' : '' }}>بسته‌بندی</option>
                    </select>
                    @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label small">تعداد <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control form-control-sm @error('quantity') is-invalid @enderror"
                           value="{{ old('quantity', $production->quantity) }}" step="0.01" required>
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label small">زمان (ساعت)</label>
                    <input type="number" name="time_hours" class="form-control form-control-sm @error('time_hours') is-invalid @enderror"
                           value="{{ old('time_hours', $production->time_hours) }}" step="0.01" min="0">
                    @error('time_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3" id="press-group" style="{{ $production->stage == 'production' ? 'display:block;' : 'display:none;' }}">
                    <label class="form-label small">پرس</label>
                    <select name="press_id" id="press_id" class="form-select form-select-sm @error('press_id') is-invalid @enderror">
                        <option value="">انتخاب پرس...</option>
                        @foreach($presses as $pr)
                            <option value="{{ $pr->id }}" {{ old('press_id', $production->press_id) == $pr->id ? 'selected' : '' }}>{{ $pr->name }}</option>
                        @endforeach
                    </select>
                    @error('press_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- توقف‌های تولید --}}
            <div class="card bg-light mt-3">
                <div class="card-body py-2">
                    <h6 class="fw-bold small">توقف‌های تولید</h6>
                    <div class="row g-2 align-items-end" id="stops-container">
                        @foreach($production->stops as $index => $stop)
                        <div class="col-md-3 stop-row">
                            <select name="stop_types[]" class="form-select form-select-sm">
                                <option value="machine_failure" {{ $stop->type == 'machine_failure' ? 'selected' : '' }}>خرابی ماشین</option>
                                <option value="mold_change_repair" {{ $stop->type == 'mold_change_repair' ? 'selected' : '' }}>تعویض قالب</option>
                            </select>
                        </div>
                        <div class="col-md-2 stop-row">
                            <input type="number" name="stop_hours[]" class="form-control form-control-sm" placeholder="ساعت" step="0.01" min="0" value="{{ $stop->hours }}">
                        </div>
                        <div class="col-md-1 stop-row">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.stop-row').remove()">✖</button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addStopRow()">➕ افزودن توقف</button>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i> بروزرسانی</button>
                <a href="{{ route('productions.index') }}" class="btn btn-secondary btn-sm ms-2">انصراف</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function addStopRow() {
        const container = document.getElementById('stops-container');
        const html = `
            <div class="col-md-3 stop-row">
                <select name="stop_types[]" class="form-select form-select-sm">
                    <option value="machine_failure">خرابی ماشین</option>
                    <option value="mold_change_repair">تعویض قالب</option>
                </select>
            </div>
            <div class="col-md-2 stop-row">
                <input type="number" name="stop_hours[]" class="form-control form-control-sm" placeholder="ساعت" step="0.01" min="0">
            </div>
            <div class="col-md-1 stop-row">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.stop-row').remove()">✖</button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
</script>
@endpush
@endsection