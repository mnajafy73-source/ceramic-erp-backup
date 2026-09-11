@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .wizard-wrapper {
        max-width: 850px;
        margin: 0 auto;
    }
    .wizard-card {
        background: #fff;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .wizard-header {
        background: linear-gradient(135deg, #1e3a5f, #2b5f8e);
        color: #fff;
        padding: 22px 24px;
        border-radius: 14px 14px 0 0;
        margin: -24px -24px 24px -24px;
    }
    .wizard-header h4 {
        margin: 0;
        font-weight: bold;
    }
    .wizard-header small {
        opacity: 0.8;
    }
    .step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    .step-number {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: #0d6efd;
        color: #fff;
        border-radius: 50%;
        font-weight: bold;
        font-size: 16px;
        margin-left: 14px;
        transition: all 0.3s;
    }
    .step.disabled .step-number {
        background: #adb5bd;
    }
    .step-body {
        flex: 1;
    }
    .step-body label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }
    .step.disabled .step-body {
        opacity: 0.5;
        pointer-events: none;
    }
    .current-value-box {
        background: #e7f3ff;
        border-right: 4px solid #0d6efd;
        padding: 12px 16px;
        border-radius: 8px;
        margin-top: 14px;
        display: none;
    }
    .current-value-box.active {
        display: block;
    }
    .current-value-box .cvb-label {
        font-size: 12px;
        color: #555;
    }
    .current-value-box .cvb-value {
        font-size: 22px;
        font-weight: bold;
        color: #0d6efd;
    }
    .current-value-box .cvb-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
    .submit-section {
        text-align: center;
        padding-top: 14px;
        border-top: 1px dashed #dee2e6;
        margin-top: 24px;
    }
    .submit-section button {
        min-width: 220px;
    }
    .reset-section {
        text-align: center;
        margin-top: 30px;
        font-size: 13px;
    }
    .ts-wrapper.single .ts-control {
        padding: 8px 12px;
        min-height: 44px;
        border-radius: 8px;
    }
    .ts-wrapper.focus .ts-control {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.15);
    }
    .quantity-input {
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        direction: ltr;
        font-family: 'Courier New', monospace;
        min-height: 48px;
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">🛠️ به‌روزرسانی دستی موجودی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item"><a href="{{ route('settings.manual-inventory') }}">تنظیمات</a></li>
            <li class="breadcrumb-item active">به‌روزرسانی موجودی</li>
        </ol>
    </nav>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show wizard-wrapper" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger wizard-wrapper">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="wizard-wrapper">
    <div class="wizard-card">
        <div class="wizard-header">
            <h4>به‌روزرسانی موجودی</h4>
            <small>نوع موجودی، محصول و مقدار جدید را انتخاب کنید</small>
        </div>

        <form method="POST" action="{{ route('settings.manual-inventory.update') }}" id="inventoryForm">
            @csrf
            @method('PUT')

            <input type="hidden" name="type" id="hidden_type">
            <input type="hidden" name="item_id" id="hidden_item_id">

            <!-- مرحله ۱: نوع موجودی -->
            <div class="step" id="step1">
                <div class="step-number">۱</div>
                <div class="step-body">
                    <label for="inventory_type_select">نوع موجودی را انتخاب کنید</label>
                    <select id="inventory_type_select" placeholder="جستجو یا انتخاب کنید...">
                        <option value="">— انتخاب کنید —</option>
                        @foreach($inventoryData as $key => $data)
                            <option value="{{ $key }}">{{ $data['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- مرحله ۲: انتخاب محصول -->
            <div class="step disabled" id="step2">
                <div class="step-number">۲</div>
                <div class="step-body">
                    <label for="item_select">محصول / ماده را انتخاب کنید</label>
                    <select id="item_select" placeholder="ابتدا نوع موجودی را انتخاب کنید...">
                        <option value="">— انتخاب کنید —</option>
                    </select>
                </div>
            </div>

            <!-- مرحله ۳: مقدار جدید -->
            <div class="step disabled" id="step3">
                <div class="step-number">۳</div>
                <div class="step-body">
                    <label for="quantity_input">مقدار جدید</label>
                    <input type="text"
                           id="quantity_input"
                           name="quantity"
                           class="form-control quantity-input"
                           placeholder="0"
                           autocomplete="off"
                           inputmode="numeric">
                </div>
            </div>

            <!-- نمایش مقدار فعلی -->
            <div class="current-value-box" id="currentValueBox">
                <div class="cvb-label">مقدار فعلی در سیستم:</div>
                <div class="cvb-value"><span id="cvbValue">0</span> <span id="cvbUnit"></span></div>
                <div class="cvb-hint" id="cvbHint"></div>
            </div>

            <!-- دکمه ذخیره -->
            <div class="submit-section">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                    <i class="fas fa-save me-2"></i> ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>

    <!-- دکمه صفر کردن (پایین صفحه، کوچک) -->
    <div class="reset-section">
        <button type="button" class="btn btn-link text-danger btn-sm" onclick="confirmReset()">
            <i class="fas fa-trash-alt me-1"></i> صفر کردن همه موجودی‌ها
        </button>
    </div>
</div>

<form id="resetForm" action="{{ route('settings.manual-inventory.reset') }}" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function() {
    var inventoryData = @json($inventoryData);

    var typeSelect     = document.getElementById('inventory_type_select');
    var itemSelect     = document.getElementById('item_select');
    var quantityInput  = document.getElementById('quantity_input');
    var submitBtn      = document.getElementById('submitBtn');
    var step2          = document.getElementById('step2');
    var step3          = document.getElementById('step3');
    var currentBox     = document.getElementById('currentValueBox');
    var cvbValue       = document.getElementById('cvbValue');
    var cvbUnit        = document.getElementById('cvbUnit');
    var cvbHint        = document.getElementById('cvbHint');
    var hiddenType     = document.getElementById('hidden_type');
    var hiddenItemId   = document.getElementById('hidden_item_id');
    var form           = document.getElementById('inventoryForm');

    // Tom Select برای نوع موجودی
    var typeTom = new TomSelect('#inventory_type_select', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'جستجو یا انتخاب کنید...',
        onChange: function(value) {
            handleTypeChange(value);
        }
    });

    // Tom Select برای محصول
    var itemTom = new TomSelect('#item_select', {
        create: false,
        sortField: { field: 'text', direction: 'asc' },
        placeholder: 'جستجو یا انتخاب کنید...',
        onChange: function(value) {
            handleItemChange(value);
        }
    });

    function handleTypeChange(typeKey) {
        // ریست
        itemTom.clear(true);
        itemTom.clearOptions();
        quantityInput.value = '';
        currentBox.classList.remove('active');
        submitBtn.disabled = true;
        step2.classList.add('disabled');
        step3.classList.add('disabled');
        hiddenType.value = '';
        hiddenItemId.value = '';

        if (!typeKey || !inventoryData[typeKey]) return;

        // فعال‌سازی مرحله ۲
        step2.classList.remove('disabled');
        hiddenType.value = typeKey;

        // پر کردن گزینه‌های محصول
        var items = inventoryData[typeKey].items || [];
        var options = items.map(function(item) {
            return {
                value: String(item.id),
                text: item.name,
                itemData: item
            };
        });

        itemTom.addOptions(options);
    }

    function handleItemChange(itemId) {
        currentBox.classList.remove('active');
        quantityInput.value = '';
        submitBtn.disabled = true;
        step3.classList.add('disabled');
        hiddenItemId.value = '';

        if (!itemId) return;

        var typeKey = hiddenType.value;
        if (!typeKey || !inventoryData[typeKey]) return;

        var item = inventoryData[typeKey].items.find(function(x) {
            return String(x.id) === String(itemId);
        });

        if (!item) return;

        // فعال‌سازی مرحله ۳
        step3.classList.remove('disabled');
        hiddenItemId.value = itemId;

        // نمایش مقدار فعلی
        cvbValue.textContent = formatNumber(item.value);
        cvbUnit.textContent = inventoryData[typeKey].unit || '';
        cvbHint.textContent = item.hint || '';
        currentBox.classList.add('active');

        // فوکوس روی فیلد مقدار
        setTimeout(function() {
            quantityInput.focus();
            quantityInput.select();
        }, 200);
    }

    // فرمت‌دهی عدد با ویرگول
    function formatNumber(value) {
        var num = String(value).replace(/,/g, '');
        if (num === '' || isNaN(num)) return '0';
        var parts = num.split('.');
        var integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.length > 1 ? integerPart + '.' + parts[1] : integerPart;
    }

    // فرمت‌دهی زنده‌ی فیلد مقدار
    quantityInput.addEventListener('input', function() {
        var cursorPosition = this.selectionStart;
        var rawValue = this.value.replace(/[^0-9.]/g, '');
        var formatted = formatNumber(rawValue);
        this.value = formatted;

        var newCursor = cursorPosition + (formatted.length - this.value.length);
        try { this.setSelectionRange(newCursor, newCursor); } catch(e) {}

        // فعال/غیرفعال کردن دکمه ذخیره
        var clean = rawValue.trim();
        submitBtn.disabled = clean === '' || isNaN(clean);
    });

    // قبل از ارسال فرم، ویرگول‌ها را حذف کن
    form.addEventListener('submit', function(e) {
        var rawValue = quantityInput.value.replace(/[^0-9.]/g, '');
        quantityInput.value = rawValue;
    });

    // پیام موفقیت رو بعد از ۳ ثانیه ببند
    setTimeout(function() {
        var alert = document.querySelector('.alert-success');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(function() { alert.remove(); }, 400);
        }
    }, 3000);
})();

function confirmReset() {
    if (confirm('⚠️ هشدار! آیا از صفر کردن همه موجودی‌ها مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
        if (confirm('تأیید نهایی: آیا مطمئن هستید؟')) {
            document.getElementById('resetForm').submit();
        }
    }
}
</script>
@endpush