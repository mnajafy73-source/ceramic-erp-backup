@extends('layouts.app')

@section('title', 'ثبت تولید جدید')

@push('scripts')
<script>
    // Persian Datepicker (اگر بارگذاری نشده باشد، خطا نمی‌دهد)
    try {
        $('#date').persianDatepicker({
            format: 'YYYY/MM/DD',
            autoClose: true,
            initialValue: false,
            observer: true,
            calendar:{
                persian: { locale: 'fa' }
            }
        });
    } catch(e) {}

    // مدیریت پرس با JavaScript خالص برای اطمینان
    document.addEventListener('DOMContentLoaded', function() {
        var stageSelect = document.getElementById('stage');
        var pressGroup = document.getElementById('press-group');
        var pressSelect = document.getElementById('press_id');

        function togglePress() {
            if (stageSelect.value === 'production') {
                pressGroup.style.display = 'block';
                pressSelect.disabled = false;
            } else {
                pressGroup.style.display = 'none';
                pressSelect.disabled = true;
                pressSelect.value = ''; // پاک کردن مقدار قبلی
            }
        }

        stageSelect.addEventListener('change', togglePress);
        // اجرای اولیه
        togglePress();
    });
</script>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت تولید جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('productions.index') }}">تولیدات</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('productions.store') }}" method="POST">
            @csrf
            {{-- ردیف ۱: تاریخ و اپراتور --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date" class="form-label">تاریخ <span class="text-danger">*</span></label>
                    <input type="text" name="date" id="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', $yesterday ?? '') }}" required autocomplete="off">
                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="operator_id" class="form-label">اپراتور <span class="text-danger">*</span></label>
                    <select name="operator_id" id="operator_id" class="form-select @error('operator_id') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($operators as $operator)
                            <option value="{{ $operator->id }}" {{ old('operator_id') == $operator->id ? 'selected' : '' }}>{{ $operator->name }}</option>
                        @endforeach
                    </select>
                    @error('operator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- ردیف ۲: محصول و عملیات --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="product_id" class="form-label">محصول <span class="text-danger">*</span></label>
                    <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="stage" class="form-label">عملیات <span class="text-danger">*</span></label>
                    <select name="stage" id="stage" class="form-select @error('stage') is-invalid @enderror" required>
                        <option value="">انتخاب کنید...</option>
                        <option value="production" {{ old('stage') == 'production' ? 'selected' : '' }}>تولید</option>
                        <option value="payment" {{ old('stage') == 'payment' ? 'selected' : '' }}>پرداخت</option>
                        <option value="packaging" {{ old('stage') == 'packaging' ? 'selected' : '' }}>بسته‌بندی</option>
                    </select>
                    @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- ردیف ۳: پرس (فقط وقتی عملیات = تولید)، تعداد، زمان --}}
            <div class="row">
                <div class="col-md-6 mb-3" id="press-group" style="display:none;">
                    <label for="press_id" class="form-label">پرس <span class="text-danger">*</span></label>
                    <select name="press_id" id="press_id" class="form-select @error('press_id') is-invalid @enderror">
                        <option value="">انتخاب کنید...</option>
                        @foreach($presses as $press)
                            <option value="{{ $press->id }}" {{ old('press_id') == $press->id ? 'selected' : '' }}>{{ $press->name }}</option>
                        @endforeach
                    </select>
                    @error('press_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label for="quantity" class="form-label">تعداد <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" id="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required min="0" step="0.01">
                    @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label for="time_hours" class="form-label">زمان (ساعت)</label>
                    <input type="number" name="time_hours" id="time_hours" class="form-control @error('time_hours') is-invalid @enderror" value="{{ old('time_hours') }}" min="0" step="0.01">
                    @error('time_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- توقف‌ها --}}
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">➕ توقف‌های تولید</h6>
                    <div id="stops-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addStopRow()">
                        <i class="fas fa-plus-circle"></i> افزودن توقف
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
            <a href="{{ route('productions.index') }}" class="btn btn-secondary me-2">انصراف</a>
        </form>
    </div>
</div>

{{-- اسکریپت توقف‌ها (با JavaScript خالص برای اطمینان) --}}
<script>
    let stopIndex = 0;
    function addStopRow() {
        const container = document.getElementById('stops-container');
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 stop-row';
        div.id = 'stop-row-' + stopIndex;
        div.innerHTML = `
            <div class="col-md-5">
                <select name="stop_types[]" class="form-select" required>
                    <option value="">نوع توقف...</option>
                    <option value="machine_failure">خرابی دستگاه</option>
                    <option value="mold_change_repair">تعویض/تعمیر قالب</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="number" name="stop_hours[]" class="form-control" placeholder="مدت (ساعت)" min="0" step="0.01" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.stop-row').remove()">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
        stopIndex++;
    }

    // بارگذاری توقف‌های قدیمی در صورت وجود
    document.addEventListener('DOMContentLoaded', function() {
        @if(old('stop_types'))
            @foreach(old('stop_types') as $i => $type)
                @if(!empty($type))
                    addStopRow();
                    setTimeout(function() {
                        const rows = document.querySelectorAll('.stop-row');
                        const lastRow = rows[rows.length - 1];
                        if (lastRow) {
                            lastRow.querySelector('select').value = '{{ $type }}';
                            lastRow.querySelector('input').value = '{{ old('stop_hours')[$i] }}';
                        }
                    }, 50);
                @endif
            @endforeach
        @endif
    });
</script>
@endsection