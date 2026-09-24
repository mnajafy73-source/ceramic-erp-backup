<?php $__env->startSection('title', 'مواد سازی'); ?>

<?php $__env->startPush('styles'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .filter-bar {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        padding: 12px 16px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
        align-items: center;
    }
    .filter-bar .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        padding: 5px 16px;
    }

    #addMaterialModal .modal-header {
        background: linear-gradient(135deg, #198754, #20c997);
        color: #fff;
    }
    #addMaterialModal .modal-header .btn-close {
        filter: invert(1) brightness(2);
    }
    #addMaterialModal .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #495057;
    }

    .datepicker-plot-area {
        font-family: Tahoma, sans-serif !important;
        z-index: 99999 !important;
    }
    .select2-container--open {
        z-index: 99999 !important;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1">📋 لیست مواد سازی</h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">مواد سازی</li>
        </ol>
    </nav>
</div>


<div class="filter-bar">
    <span class="fw-bold text-muted small me-2">
        <i class="fas fa-filter me-1"></i> فیلتر منبع:
    </span>

    <a href="<?php echo e(route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'all']))); ?>"
       class="btn btn-sm <?php echo e($source === 'all' ? 'btn-dark' : 'btn-outline-dark'); ?>">
        <i class="fas fa-list"></i> همه
    </a>

    <a href="<?php echo e(route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'manual']))); ?>"
       class="btn btn-sm <?php echo e($source === 'manual' ? 'btn-success' : 'btn-outline-success'); ?>">
        <i class="fas fa-hand-paper"></i> دستی
    </a>

    <a href="<?php echo e(route('material-making.index', array_merge(request()->except('source', 'page'), ['source' => 'imported']))); ?>"
       class="btn btn-sm <?php echo e($source === 'imported' ? 'btn-primary' : 'btn-outline-primary'); ?>">
        <i class="fas fa-file-excel"></i> اکسل
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <button type="button" class="btn btn-success"
                    data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                <i class="fas fa-plus-circle me-1"></i> ثبت مواد سازی جدید
            </button>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-1"></i> <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if(session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-1"></i> <?php echo e(session('error')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if($records->count()): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ردیف</th>
                            <th>سال</th>
                            <th>ماه</th>
                            <th>روز</th>
                            <th>نام</th>
                            <th>فرمول</th>
                            <th>تعداد بالمیل</th>
                            <th>وزن بالمیل (کیلوگرم)</th>
                            <th class="text-center">منبع</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($records->firstItem() + $index); ?></td>
                                <td><?php echo e($record->year); ?></td>
                                <td><?php echo e($record->month); ?></td>
                                <td><?php echo e($record->day); ?></td>
                                <td><?php echo e($record->name ?? '-'); ?></td>
                                <td><?php echo e($record->material); ?></td>
                                <td><?php echo e(number_format($record->quantity)); ?></td>
                                <td><?php echo e(number_format($record->mill_weight / 1000, 2)); ?></td>

                                
                                <td class="text-center">
                                    <?php if($record->is_imported): ?>
                                        <span class="badge bg-primary">اکسل</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">دستی</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <form action="<?php echo e(route('material-making.destroy', $record->id)); ?>" method="POST" class="d-inline"
                                          onsubmit="return confirm('آیا از حذف این رکورد مطمئن هستید؟')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3"><?php echo e($records->links()); ?></div>
        <?php else: ?>
            <div class="alert alert-info">
                <?php if($source === 'manual'): ?>
                    هیچ رکورد دستی ثبت نشده است.
                <?php elseif($source === 'imported'): ?>
                    هیچ رکورد ایمپورتی (اکسل) وجود ندارد.
                <?php else: ?>
                    هیچ رکوردی ثبت نشده است.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>




<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    ثبت مواد سازی جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form action="<?php echo e(route('material-making.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">تاریخ <span class="text-danger">*</span></label>
                            <input type="text" name="date" id="date_input"
                                   class="form-control jalali-date-input"
                                   value="<?php echo e(old('date', \Morilog\Jalali\Jalalian::now()->format('Y/m/d'))); ?>"
                                   autocomplete="off" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">نام (اختیاری)</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?php echo e(old('name')); ?>"
                                   placeholder="مثلاً: بالمیل ۱">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">فرمول <span class="text-danger">*</span></label>
                            <select name="material" id="material_select" class="form-select" required>
                                <option value="">— انتخاب فرمول —</option>
                                <?php $__currentLoopData = $formulas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($f->name); ?>" <?php echo e(old('material') == $f->name ? 'selected' : ''); ?>>
                                        <?php echo e($f->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">تعداد بالمیل <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" class="form-control"
                                   value="<?php echo e(old('quantity')); ?>" min="0.01" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">وزن بالمیل (کیلوگرم) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="mill_weight" class="form-control"
                                   value="<?php echo e(old('mill_weight')); ?>" min="0.01" required>
                        </div>

                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        با ثبت این رکورد، مواد اولیه به‌طور خودکار از انبار کسر می‌شود.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> انصراف
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> ثبت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
    $(document).ready(function() {
        // Select2 برای فرمول
        if ($.fn.select2) {
            $('#material_select').select2({
                placeholder: 'جستجو و انتخاب فرمول...',
                allowClear: true,
                width: '100%',
                dir: 'rtl',
                dropdownParent: $('#addMaterialModal')
            });
        }

        // Date picker
        if (typeof $.fn.pDatepicker !== 'undefined') {
            $('#date_input').pDatepicker({
                format: 'YYYY/MM/DD',
                initialValue: false,
                autoClose: true,
                persianDigit: false,
                observer: true,
                calendar: {
                    persian: { locale: 'fa', leapYearMode: 'algorithmic' }
                },
                toolbox: { calendarSwitch: { enabled: false } },
                navigator: { scroll: { enabled: true } },
                timePicker: { enabled: false }
            });
        }

        // اگه خطای اعتبارسنجی داشتیم، مدال رو باز کن
        <?php if($errors->any()): ?>
            new bootstrap.Modal(document.getElementById('addMaterialModal')).show();
        <?php endif; ?>
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/material-making/index.blade.php ENDPATH**/ ?>