<?php $__env->startPush('styles'); ?>
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
    .inline-sel-carton { min-width: 120px; }
    .inline-sel-layer  { min-width: 120px; }
    .inline-sel-type   { min-width: 100px; }

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
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">مدیریت کالاها</h4>
    <a href="<?php echo e(route('products.create')); ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> کالای جدید
    </a>
</div>


<?php
    $currentCategory = request('category', 'all');
    $typeLabels = \App\Models\Product::typeLabels();

    // شمارش هر دسته
    $counts = [
        'all' => \App\Models\Product::count(),
    ];
    foreach ($typeLabels as $key => $label) {
        $counts[$key] = \App\Models\Product::where('product_type', $key)->count();
    }
?>

<div class="category-filter-bar">
    <div class="filter-title">
        <i class="fas fa-filter me-1"></i>
        دسته‌بندی:
    </div>

    <a href="<?php echo e(route('products.index', array_merge(request()->except('category', 'page'), ['category' => 'all']))); ?>"
       class="btn <?php echo e($currentCategory === 'all' ? 'btn-dark active' : 'btn-outline-dark'); ?>">
        <i class="fas fa-list"></i>
        همه
        <span class="badge-count"><?php echo e(number_format($counts['all'])); ?></span>
    </a>

    <?php $__currentLoopData = $typeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
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
        ?>
        <a href="<?php echo e(route('products.index', array_merge(request()->except('category', 'page'), ['category' => $key]))); ?>"
           class="btn <?php echo e($currentCategory === $key ? 'btn-' . $colors[$key] . ' active' : 'btn-outline-' . $colors[$key]); ?>">
            <i class="fas <?php echo e($icons[$key]); ?>"></i>
            <?php echo e($label); ?>

            <span class="badge-count"><?php echo e(number_format($counts[$key])); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?php echo e(route('products.index')); ?>" method="GET" class="row g-3 align-items-end">
            
            <?php if($currentCategory && $currentCategory !== 'all'): ?>
                <input type="hidden" name="category" value="<?php echo e($currentCategory); ?>">
            <?php endif; ?>

            <div class="col-md-6">
                <label class="form-label">جستجو</label>
                <input type="text" name="search" class="form-control" placeholder="کد یا نام کالا..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">جستجو</button>
            </div>
            <div class="col-md-2">
                <a href="<?php echo e(route('products.index', ['category' => $currentCategory])); ?>" class="btn btn-secondary w-100">حذف فیلتر</a>
            </div>
            <div class="col-md-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        مرتب‌سازی
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'asc']))); ?>">کد (صعودی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'code', 'direction' => 'desc']))); ?>">کد (نزولی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'asc']))); ?>">نام (صعودی)</a></li>
                        <li><a class="dropdown-item" href="<?php echo e(route('products.index', array_merge(request()->all(), ['sort' => 'name', 'direction' => 'desc']))); ?>">نام (نزولی)</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>
<?php if(session('error')): ?>
    <div class="alert alert-danger"><?php echo e(session('error')); ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>نام</th>
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
                    <?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr id="product-row-<?php echo e($product->id); ?>" data-product-id="<?php echo e($product->id); ?>">
                        <td class="product-name-cell"><?php echo e($product->name); ?></td>

                        
                        <td class="col-inline">
                            <select class="inline-edit inline-sel-carton"
                                    data-field="carton_packaging_id">
                                <option value="">—</option>
                                <?php $__currentLoopData = $packagings->where('type', 'carton'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pkg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($pkg->id); ?>"
                                        <?php echo e($product->carton_packaging_id == $pkg->id ? 'selected' : ''); ?>>
                                        <?php echo e($pkg->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </td>

                        
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="per_box"
                                   value="<?php echo e($product->per_box); ?>"
                                   min="0"
                                   placeholder="—">
                        </td>

                        
                        <td class="col-inline">
                            <select class="inline-edit inline-sel-layer"
                                    data-field="layer_packaging_id">
                                <option value="">—</option>
                                <?php $__currentLoopData = $packagings->where('type', 'layer'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pkg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($pkg->id); ?>"
                                        <?php echo e($product->layer_packaging_id == $pkg->id ? 'selected' : ''); ?>>
                                        <?php echo e($pkg->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </td>

                        
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="layers_per_box"
                                   value="<?php echo e($product->layers_per_box); ?>"
                                   min="0"
                                   placeholder="—">
                        </td>

                        
                        <td class="col-inline text-center">
                            <input type="number"
                                   class="inline-edit inline-num"
                                   data-field="tonneli_feed_rate"
                                   value="<?php echo e($product->tonneli_feed_rate); ?>"
                                   min="0"
                                   placeholder="—">
                        </td>

                        
                        <td class="col-inline text-center product-type-cell">
                            <select class="inline-edit inline-sel-type"
                                    data-field="product_type">
                                <?php $__currentLoopData = \App\Models\Product::typeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k); ?>"
                                        <?php echo e(($product->product_type ?? 'normal') == $k ? 'selected' : ''); ?>>
                                        <?php echo e($v); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </td>

                        
                        <td class="d-flex gap-1">
                            <button type="button"
                                    class="btn btn-sm btn-outline-warning btn-quick-edit"
                                    data-id="<?php echo e($product->id); ?>"
                                    title="ویرایش کامل">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="<?php echo e(route('products.show', $product)); ?>" class="btn btn-sm btn-outline-info" title="مشاهده">
                                <i class="fas fa-eye"></i>
                            </a>
                            <form action="<?php echo e(route('products.destroy', $product)); ?>" method="POST" onsubmit="return confirm('مطمئن هستید این کالا حذف شود؟')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center py-4">هیچ کالایی یافت نشد.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($products->links()); ?></div>




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
                <?php echo csrf_field(); ?>
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
                                <?php $__currentLoopData = \App\Models\Product::typeLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($k); ?>"><?php echo e($v); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <?php $__currentLoopData = $products->pluck('unit')->filter()->unique('id'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <?php $__currentLoopData = \App\Models\Formula::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($f->id); ?>"><?php echo e($f->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                                <?php $__currentLoopData = $packagings->where('type', 'carton'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">لایه</label>
                            <select name="layer_packaging_id" id="qe_layer_packaging_id" class="form-select">
                                <option value="">—</option>
                                <?php $__currentLoopData = $packagings->where('type', 'layer'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    var PRODUCTS_DATA = <?php echo json_encode($productsJson, 15, 512) ?>;
    var INLINE_UPDATE_URL = '<?php echo e(route("products.inline-update", ":id")); ?>';
    var CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';

    // ═══════════════════════════════════════════════════════════
    //  ویرایش درجا
    // ═══════════════════════════════════════════════════════════
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

    // ═══════════════════════════════════════════════════════════
    //  مدال ویرایش کامل
    // ═══════════════════════════════════════════════════════════
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
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/products/index.blade.php ENDPATH**/ ?>