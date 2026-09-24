<?php $__env->startPush('styles'); ?>
<style>
    .form-container {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.8rem;
        color: #1e293b;
        border-bottom: 2px solid #e9ecef;
        padding: 10px 8px;
    }
    .table td {
        padding: 6px 8px;
        vertical-align: middle;
    }
    .table .form-control-sm, .table .form-select-sm {
        font-size: 0.8rem;
        padding: 4px 8px;
        min-height: 34px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        background: #fff;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    .table .form-control-sm:focus, .table .form-select-sm:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        outline: none;
    }
    .stop-item {
        background: #f1f5f9;
        border-radius: 6px;
        padding: 4px 6px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #e2e8f0;
    }
    .stop-item .form-select-sm {
        width: 100px;
        min-height: 30px;
        font-size: 0.75rem;
        padding: 2px 4px;
    }
    .stop-item .form-control-sm {
        width: 70px;
        min-height: 30px;
        font-size: 0.75rem;
        padding: 2px 4px;
    }
    .btn-icon {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.8rem;
    }
    .btn-add-row {
        border-radius: 8px;
        padding: 6px 16px;
        font-size: 0.8rem;
        font-weight: 500;
        background: #f8fafc;
        border: 1px dashed #94a3b8;
        color: #475569;
        transition: all 0.2s;
    }
    .btn-add-row:hover {
        background: #f1f5f9;
        border-color: #3b82f6;
        color: #1e293b;
    }
    .table-hover tbody tr:hover {
        background-color: #f8fafc;
    }
    .required-star {
        color: #ef4444;
        margin-right: 2px;
    }
    .press-header {
        display: none;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    let rowIndex = 0;

    function addRow() {
        const container = document.getElementById('rows-container');
        if (!container) return;

        const html = `
            <tr id="row-${rowIndex}" class="row-item">
                <td>
                    <select name="rows[${rowIndex}][operator_id]" class="form-select form-select-sm" required>
                        <option value="">انتخاب...</option>
                        <?php $__currentLoopData = $operators; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($op->id); ?>"><?php echo e($op->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </td>
                <td>
                    <select name="rows[${rowIndex}][product_id]" class="form-select form-select-sm" required>
                        <option value="">انتخاب...</option>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>"><?php echo e($p->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </td>
                <td>
                    <select name="rows[${rowIndex}][stage]" class="form-select form-select-sm stage-select" onchange="togglePress(this, ${rowIndex})">
                        <option value="">انتخاب...</option>
                        <option value="تولید">تولید</option>
                        <option value="پرداخت">پرداخت</option>
                        <option value="بسته‌بندی">بسته‌بندی</option>
                    </select>
                </td>
                <td class="press-col" style="display: none;">
                    <select name="rows[${rowIndex}][press_id]" class="form-select form-select-sm">
                        <option value="">انتخاب...</option>
                        <?php $__currentLoopData = $presses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($pr->id); ?>"><?php echo e($pr->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="rows[${rowIndex}][quantity]" class="form-control form-control-sm" placeholder="مقدار" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="rows[${rowIndex}][time_hours]" class="form-control form-control-sm" placeholder="ساعت" step="0.01" min="0">
                </td>
                <td style="min-width: 220px;">
                    <div id="stops-container-${rowIndex}"></div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addStop(${rowIndex})" style="font-size:0.75rem; padding:2px 10px;">
                        <i class="fas fa-plus-circle"></i> افزودن توقف
                    </button>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-icon" onclick="removeRow(${rowIndex})" title="حذف ردیف">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
    }

    function addStop(index) {
        const container = document.getElementById(`stops-container-${index}`);
        if (!container) return;
        const html = `
            <div class="stop-item">
                <select name="rows[${index}][stop_types][]" class="form-select form-select-sm">
                    <option value="خرابی ماشین">خرابی ماشین</option>
                    <option value="تعویض قالب">تعویض قالب</option>
                </select>
                <input type="number" name="rows[${index}][stop_hours][]" class="form-control form-control-sm" placeholder="ساعت" step="0.01" min="0">
                <button type="button" class="btn btn-sm btn-outline-danger btn-icon" onclick="this.closest('.stop-item').remove()">✖</button>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeRow(index) {
        const row = document.getElementById(`row-${index}`);
        if (row) row.remove();
    }

    function togglePress(select, index) {
        const row = document.getElementById(`row-${index}`);
        if (!row) return;
        const pressCol = row.querySelector('.press-col');
        const pressHeader = document.querySelector('.press-header');
        if (select.value === 'تولید') {
            pressCol.style.display = 'table-cell';
            pressHeader.style.display = 'table-cell';
            pressCol.querySelector('select').setAttribute('required', 'required');
        } else {
            pressCol.style.display = 'none';
            const anyProduction = document.querySelector('.stage-select[value="تولید"]');
            if (!anyProduction) {
                pressHeader.style.display = 'none';
            }
            pressCol.querySelector('select').removeAttribute('required');
        }
    }

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
        addRow();
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">ثبت تولید جدید</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(route('productions.index')); ?>">تولید</a></li>
            <li class="breadcrumb-item active">ثبت جدید</li>
        </ol>
    </nav>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('productions.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            
            <div class="row mb-3 align-items-center">
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-0">تاریخ <span class="required-star">*</span></label>
                    <input type="text" name="date" id="date" class="form-control form-control-sm <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('date', $today ?? '')); ?>" required autocomplete="off">
                    <?php $__errorArgs = ['date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="col-md-9 text-end">
                    <span class="text-muted" style="font-size:0.8rem;"><i class="fas fa-info-circle me-1"></i>ثبت چند محصول با یک تاریخ</span>
                </div>
            </div>

            
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="min-width:120px;">اپراتور</th>
                            <th style="min-width:120px;">محصول</th>
                            <th style="min-width:110px;">عملیات</th>
                            <th class="press-header" style="min-width:100px;">پرس</th>
                            <th style="min-width:80px;">تعداد</th>
                            <th style="min-width:80px;">زمان (ساعت)</th>
                            <th style="min-width:220px;">توقف‌ها</th>
                            <th style="width:50px;" class="text-center">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="rows-container">
                        
                    </tbody>
                </table>
            </div>

            
            <div class="mt-3">
                <button type="button" class="btn-add-row" onclick="addRow()">
                    <i class="fas fa-plus-circle me-1"></i> افزودن ردیف
                </button>
            </div>

            
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> ثبت</button>
                <a href="<?php echo e(route('productions.index')); ?>" class="btn btn-secondary">بازگشت</a>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/productions/create.blade.php ENDPATH**/ ?>