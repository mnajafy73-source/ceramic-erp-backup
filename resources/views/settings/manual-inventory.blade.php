@extends('layouts.app')

@push('styles')
<style>
    .inventory-section {
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        background: #f8f9fa;
    }
    .inventory-section .section-title {
        font-weight: bold;
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .inventory-table input[type="number"] {
        width: 120px;
        text-align: left;
        direction: ltr;
    }
</style>
@endpush

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-1">🛠️ تنظیم موجودی اول دوره</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">داشبورد</a></li>
            <li class="breadcrumb-item active">تنظیم موجودی اول دوره</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            در این صفحه می‌توانید <strong>موجودی اول دوره</strong> را برای همه‌ی بخش‌ها تنظیم کنید.
            این موجودی‌ها به‌عنوان پایه در نظر گرفته می‌شوند و سیستم تغییرات بعدی را روی آن‌ها اعمال می‌کند.
            مقادیر خالی یا صفر، به‌روزرسانی نمی‌شوند.
        </div>

        <form action="{{ route('settings.manual-inventory.update') }}" method="POST">
            @csrf
            @method('PUT')

            {{-- موجودی اول دوره --}}
            @if($products->count())
            <div class="inventory-section">
                <h5 class="section-title">📦 موجودی اول دوره (پایه)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی اول دوره</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="opening[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $openingInventories->has($product->id) ? $openingInventories[$product->id]->quantity : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- موجودی خام (فقط نمایش) --}}
            <div class="inventory-section">
                <h5 class="section-title">📊 موجودی خام (محاسبه‌شده از سیستم)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr>
                                <th>نام محصول</th>
                                <th>موجودی خام</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td class="fw-bold">{{ number_format($rawStocks[$product->id] ?? 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <small class="text-muted">موجودی خام به‌صورت خودکار محاسبه می‌شود و قابل ویرایش دستی نیست.</small>
            </div>

            {{-- سایر موجودی‌ها --}}
            <div class="inventory-section">
                <h5 class="section-title">🔥 موجودی موم (۹۰۰ درجه)</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام محصول</th><th>موجودی موم</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="wax[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $waxInventories->has($product->id) ? $waxInventories[$product->id]->stock : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="inventory-section">
                <h5 class="section-title">🔥 موجودی ۱۳۰۰ درجه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام محصول</th><th>موجودی ۱۳۰۰ درجه</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="glaze1300[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $glaze1300Inventories->has($product->id) ? $glaze1300Inventories[$product->id]->stock : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="inventory-section">
                <h5 class="section-title">🏭 موجودی انبار</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام محصول</th><th>موجودی انبار</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="warehouse[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $warehouseInventories->has($product->id) ? $warehouseInventories[$product->id]->stock : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="inventory-section">
                <h5 class="section-title">🧴 موجودی شانه شده</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام محصول</th><th>موجودی شانه شده</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="shoulder[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $shoulderInventories->has($product->id) ? $shoulderInventories[$product->id]->stock : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="inventory-section">
                <h5 class="section-title">🗑️ ضایعات موم</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام محصول</th><th>ضایعات موم</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>
                                    <input type="number" name="waste_mum[{{ $product->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $wasteMumInventories->has($product->id) ? $wasteMumInventories[$product->id]->stock : 0 }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- مواد اولیه --}}
            @if($rawMaterials->count())
            <div class="inventory-section">
                <h5 class="section-title">🧪 موجودی مواد اولیه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نام ماده</th><th>موجودی (گرم)</th></tr>
                        </thead>
                        <tbody>
                            @foreach($rawMaterials as $material)
                            <tr>
                                <td>{{ $material->name }}</td>
                                <td>
                                    <input type="number" name="raw_material[{{ $material->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $material->stock }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- کارتن و لایه --}}
            @if($packagings->count())
            <div class="inventory-section">
                <h5 class="section-title">📦 موجودی کارتن و لایه</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover inventory-table">
                        <thead class="table-light">
                            <tr><th>نوع</th><th>نام</th><th>موجودی (عدد)</th></tr>
                        </thead>
                        <tbody>
                            @foreach($packagings as $packaging)
                            <tr>
                                <td>{{ $packaging->type == 'carton' ? 'کارتن' : 'لایه' }}</td>
                                <td>{{ $packaging->name }}</td>
                                <td>
                                    <input type="number" name="packaging[{{ $packaging->id }}]" 
                                           class="form-control form-control-sm" 
                                           value="{{ $packaging->stock }}"
                                           min="0" step="1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mt-4 gap-3 flex-wrap">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i> ذخیره همه موجودی‌ها
                </button>
                <div>
                    <button type="button" class="btn btn-danger btn-lg" onclick="confirmReset()">
                        <i class="fas fa-trash-alt me-2"></i> صفر کردن همه موجودی‌ها
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i> انصراف
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="resetForm" action="{{ route('settings.manual-inventory.reset') }}" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
    function confirmReset() {
        if (confirm('⚠️ هشدار! آیا از صفر کردن همه موجودی‌ها مطمئن هستید؟ این عمل غیرقابل بازگشت است.')) {
            if (confirm('تأیید نهایی: آیا مطمئن هستید که می‌خواهید همه موجودی‌ها را صفر کنید؟')) {
                document.getElementById('resetForm').submit();
            }
        }
    }
</script>
@endsection