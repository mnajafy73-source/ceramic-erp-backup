@extends('layouts.app')

@push('styles')
<style>
    .inline-edit {
        font-size: 12px;
        padding: 3px 6px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
        width: 100%;
        max-width: 100%;
    }
    .inline-edit:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13,110,253,0.15);
        outline: none;
    }
    .inline-edit:disabled {
        opacity: 0.6;
        cursor: wait;
    }
    .inline-edit.saved-flash {
        background: #d1e7dd !important;
        border-color: #198754 !important;
    }
    .inline-edit.error-flash {
        background: #f8d7da !important;
        border-color: #dc3545 !important;
    }

    .inline-num {
        width: 80px !important;
        text-align: center;
    }
    .inline-sel-carton { min-width: 110px; }
    .inline-sel-layer  { min-width: 110px; }
    .inline-sel-type   { min-width: 90px; }
    .inline-sel-formula { min-width: 120px; }

    .col-inline { padding: 4px 6px !important; }

    /* ✅ فیلتر دسته‌بندی */
    .category-filter-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px 16px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
    }
    .category-filter-bar .filter-title {
        font-weight: 600;
        color: #495057;
        font-size: 13px;
        display: flex;
        align-items: center;
        margin-right: 8px;
    }
    .category-filter-bar .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .category-filter-bar .btn .badge-count {
        background: rgba(0,0,0,0.15);
        color: inherit;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: bold;
    }
    .category-filter-bar .btn.active .badge-count {
        background: rgba(255,255,255,0.3);
    }

    #quickEditModal .modal-body { padding: 20px; }
    #quickEditModal .form-label { font-weight: 600; font-size: 13px; }
    #quickEditModal .section-title {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 13px;
        margin: 16px 0 12px;
        border-right: 3px solid #0d6efd;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">مدیریت کالاها</h4>
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> کالای جدید
    </a>
</div>

{{-- نوار فیلتر دسته‌بندی --}}
@php
    $currentCategory = request('category', 'all');
    $typeLabels = \App\Models\Product::typeLabels();

    $counts = [
        'all' => \App\Models\Product::count(),
    ];
    foreach ($typeLabels as $key => $label) {
        $counts[$key] = \App\Models\Product::where('product_type', $key)->count();
    }
@endphp

<div class="category-filter-bar">
    <div class="filter-title">
        <i class="fas fa-filter me-1"></i>
        دسته‌بندی:
    </div>

    <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}"
       class="btn {{ $currentCategory === 'all' ? 'btn-dark active' : 'btn-outline-dark' }}">
        <i class="fas fa-list"></i>
        همه
        <span class="badge-count">{{ number_format($counts['all']) }}</span>
    </a>

    @foreach($typeLabels as $key => $label)
        @php
            $colors = [
                'normal'    => 'success',
                'rod'       => 'primary',
                'pipe'      => 'info',
                'injection' => 'warning',
            ];
            $icons = [
                'normal'    => 'fa-cube',
                'rod'       => 'fa-grip-lines',
                'pipe'      => 'fa-circle-notch',
                'injection' => 'fa-syringe',
            ];
        @endphp
        <a href="{{ route('products.index', array_merge(request()->except('category', 'page'), ['category' => $key])) }}"
           class="btn {{ $currentCategory === $key ? 'btn-' . $colors[$key] . ' active' : 'btn-outline-' . $colors[$key] }}">
            <i class="fas {{ $icons[$key] }}"></i>
            {{ $label }}
            <span class="badge-count">{{ number_format($counts[$key]) }}</span>
        </a>
    @endforeach
</div>

{{-- جستجو --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('products.index') }}" method="GET" class="row g-3 align-items-end">
            @if($currentCategory && $currentCategory !== 'all')
                <input type="hidden" name="category" value="{{ $currentCategory }}">
            @endif

            <div class="col-md-6">
                <label class="form-label">جستجو</label>
                <input type="text" name="search" class="form-control" placeholder="کد یا نام کالا..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('products.index', ['category' => $currentCategory]) }}" class="btn btn-secondary w-100">حذف فیلتر</a>
            </div>
            <div class="col-md-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        مرتب‌سازی
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'asc'])) }}">کد (صعودی)</a></li>
                        <li><a class="dropdown-item" href="{{ route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'desc'])) }}">کد (نزولی)</a></li>
                        <li><a class="dropdown-item" href="{{ route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'asc'])) }}">نام (صعودی)</a></li>
                        <li><a class="dropdown-item" href="{{ route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'desc'])) }}">نام (نزولی)</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>نام</th>
                        <th class="text-center">فرمول</th>
                        <th class="text-center">کارتن</th>
                        <th class="text-center">تعداد در کارتن</th>
                        <th class="text-center">لایه</th>
                        <th class="text-center">تعداد لایه</th>
                        <th class="text-center">خوراک تونلی</th>
                        <th class="text-center">دسته‌بندی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr id="product-row-{{ $product->id }}" data-product-id="{{ $product->id }}">
                        <td class="product-name-cell">{{ $product->name }}</td>

                        {{-- ✅ فرمول --}}
                        <td class="col-inline">
                            <select class="inline-edit inline-sel-formula"
                                    data-field="formula_id">
                                <option value="">—</option>
                                @foreach($formulas as $formula)
                                    <option value="{{ $formula->id }}"
                                        {{ $product->formula_id == $formula->id ? 'selected' : '' }}>
                                        {{ $formula->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- کارتن --}}
                        <td class="col-inline">
                            <select class="inline-edit inline-sel-carton"
                                    data-field="carton_packaging_id">
                                <option value="">—</option>
                                @foreach($packagings->where('type', 'carton') as $pkg)
                                    <option value="{{ $pkg->id }}"
                                        {{ $product->carton_packaging_id == $pkg->id ? 'selected' : '' }}>
                                        {{ $pkg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- تعداد در کارتن --}}
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="per_box"
                                   value="{{ $product->per_box }}"
                                   min="0"
                                   placeholder="—">
                        </td>

                        {{-- لایه --}}
                        <td class="col-inline">
                            <select class="inline-edit inline-sel-layer"
                                    data-field="layer_packaging_id">
                                <option value="">—</option>
                                @foreach($packagings->where('type', 'layer') as $pkg)
                                    <option value="{{ $pkg->id }}"
                                        {{ $product->layer_packaging_id == $pkg->id ? 'selected' : '' }}>
                                        {{ $pkg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- تعداد لایه --}}
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="layers_per_box"
                                   value="{{ $product->layers_per_box }}"
                                   min="0"
                                   placeholder="—">
                        </td>

                        {{-- خوراک تونلی --}}
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="tonneli_feed_rate"
                                   value="{{ $product->tonneli_feed_rate }}"
                                   min="0"
                                   placeholder="—">
                        </td>

                        {{-- دسته‌بندی --}}
                        <td class="col-inline text-center product-type-cell">
                            <select class="inline-edit inline-sel-type"
                                    data-field="product_type">
                                @foreach(\App\Models\Product::typeLabels() as $k => $v)
                                    <option value="{{ $k }}"
                                        {{ ($product->product_type ?? 'normal') == $k ? 'selected' : '' }}>
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        {{-- عملیات --}}
                        <td class="d-flex gap-1">
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning btn-quick-edit"
                                    data-id="{{ $product->id }}"
                                    title="ویرایش کامل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info" title="مشاهده">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('مطمئن هستید این کالا حذف شود؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4">هیچ کالایی یافت نشد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $products->links() }}</div>

{{-- مدال ویرایش کامل --}}
<div class="modal fade" id="quickEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    ویرایش کامل: <span id="modalProductName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickEditForm">
                @csrf
                <input type="hidden" id="qe_product_id">
                <div class="modal-body">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">نام کالا</label>
                            <input type="text" name="name" id="qe_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">دسته‌بندی</label>
                            <select name="product_type" id="qe_product_type" class="form-select" required>
                                @foreach(\App\Models\Product::typeLabels() as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-boxes me-1"></i> مشخصات
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">واحد</label>
                            <select name="unit_id" id="qe_unit_id" class="form-select" required>
                                @foreach($products->pluck('unit')->filter()->unique('id') as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">وزن (گرم)</label>
                            <input type="number" step="0.01" name="weight" id="qe_weight" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">خوراک تونلی</label>
                            <input type="number" name="tonneli_feed_rate" id="qe_tonneli_feed_rate" class="form-control" min="0">
                        </div>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-fire me-1"></i> فرمول
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">فرمول</label>
                            <select name="formula_id" id="qe_formula_id" class="form-select">
                                <option value="">—</option>
                                @foreach($formulas as $f)
                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="section-title">
                        <i class="fas fa-cube me-1"></i> بسته‌بندی
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">کارتن</label>
                            <select name="carton_packaging_id" id="qe_carton_packaging_id" class="form-select">
                                <option value="">—</option>
                                @foreach($packagings->where('type', 'carton') as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">لایه</label>
                            <select name="layer_packaging_id" id="qe_layer_packaging_id" class="form-select">
                                <option value="">—</option>
                                @foreach($packagings->where('type', 'layer') as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تعداد در کارتن</label>
                            <input type="number" name="per_box" id="qe_per_box" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">لایه در کارتن</label>
                            <input type="number" name="layers_per_box" id="qe_layers_per_box" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تعداد در بسته</label>
                            <input type="number" name="per_pack" id="qe_per_pack" class="form-control" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تعداد در پالت</label>
                            <input type="number" name="per_pallet" id="qe_per_pallet" class="form-control" min="0">
                        </div>
                    </div>

                    <div id="qe_error" class="alert alert-danger mt-3 mb-0" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> انصراف
                    </button>
                    <button type="submit" class="btn btn-warning" id="qe_save_btn">
                        <i class="fas fa-save me-1"></i> ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var PRODUCTS_DATA = @json($productsJson);
    var INLINE_UPDATE_URL = '{{ route("products.inline-update", ":id") }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';

    // ویرایش درجا
    $(document).on('change', '.inline-edit', function() {
        var $el     = $(this);
        var $row    = $el.closest('tr');
        var id      = $row.data('product-id');
        var field   = $el.data('field');
        var value   = $el.val();
        var url     = INLINE_UPDATE_URL.replace(':id', id);

        $el.prop('disabled', true).removeClass('saved-flash error-flash');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: CSRF_TOKEN,
                field: field,
                value: value,
            },
            success: function(res) {
                $el.prop('disabled', false);
                if (res.success) {
                    $el.addClass('saved-flash');
                    setTimeout(function() {
                        $el.removeClass('saved-flash');
                    }, 800);

                    if (PRODUCTS_DATA[id]) {
                        PRODUCTS_DATA[id][field] = res.value;
                    }
                }
            },
            error: function(xhr) {
                $el.prop('disabled', false).addClass('error-flash');
                setTimeout(function() {
                    $el.removeClass('error-flash');
                }, 1500);

                var msg = 'خطا در ذخیره‌سازی';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = Object.keys(xhr.responseJSON.errors)[0];
                    msg = xhr.responseJSON.errors[first][0];
                }
                showToast('❌ ' + msg, '#dc3545');
            }
        });
    });

    // مدال ویرایش کامل
    $(document).on('click', '.btn-quick-edit', function() {
        var id = $(this).data('id');
        var data = PRODUCTS_DATA[id];
        if (!data) return;

        $('#qe_product_id').val(data.id);
        $('#modalProductName').text(data.name);
        $('#qe_name').val(data.name);
        $('#qe_unit_id').val(data.unit_id);
        $('#qe_product_type').val(data.product_type);
        $('#qe_weight').val(data.weight);
        $('#qe_tonneli_feed_rate').val(data.tonneli_feed_rate);
        $('#qe_formula_id').val(data.formula_id);
        $('#qe_carton_packaging_id').val(data.carton_packaging_id);
        $('#qe_layer_packaging_id').val(data.layer_packaging_id);
        $('#qe_per_box').val(data.per_box);
        $('#qe_layers_per_box').val(data.layers_per_box);
        $('#qe_per_pack').val(data.per_pack);
        $('#qe_per_pallet').val(data.per_pallet);
        $('#qe_error').hide();

        new bootstrap.Modal(document.getElementById('quickEditModal')).show();
    });

    $('#quickEditForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#qe_product_id').val();
        var btn = $('#qe_save_btn');
        var errBox = $('#qe_error');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> در حال ذخیره...');
        errBox.hide();

        $.ajax({
            url: '/products/' + id + '/quick-update',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ذخیره');
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('quickEditModal')).hide();
                    showToast('✅ ' + res.message, '#198754');
                    setTimeout(function() { location.reload(); }, 800);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i> ذخیره');
                var msg = 'خطا در ذخیره‌سازی';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = Object.keys(xhr.responseJSON.errors)[0];
                    msg = xhr.responseJSON.errors[first][0];
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                errBox.text(msg).show();
            }
        });
    });

    function showToast(msg, bg) {
        var t = document.createElement('div');
        t.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;' +
            'background:' + bg + ';color:#fff;padding:12px 24px;border-radius:8px;font-weight:bold;' +
            'box-shadow:0 4px 12px rgba(0,0,0,0.2);font-size:14px;';
        t.innerHTML = msg;
        document.body.appendChild(t);
        setTimeout(function() {
            t.style.transition = 'opacity 0.5s';
            t.style.opacity = '0';
            setTimeout(function() { t.remove(); }, 500);
        }, 2000);
    }
</script>
@endpush