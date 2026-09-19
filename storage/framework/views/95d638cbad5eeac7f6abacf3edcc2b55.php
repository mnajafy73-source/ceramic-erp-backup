

<?php $__env->startSection('title', 'آخرین تغییرات موجودی'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .log-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px 16px;
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        margin-bottom: 16px;
    }
    .log-filters .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 13px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-filters .btn .badge-count {
        background: rgba(0,0,0,0.15);
        color: inherit;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: bold;
    }
    .log-filters .btn.active .badge-count {
        background: rgba(255,255,255,0.3);
    }

    .log-row {
        border-right: 4px solid #dee2e6;
        transition: background 0.15s;
    }
    .log-row:hover {
        background: #f8f9fa;
    }
    .log-row.positive { border-right-color: #198754; }
    .log-row.negative { border-right-color: #dc3545; }
    .log-row.neutral  { border-right-color: #6c757d; }

    .log-subject-name {
        font-size: 15px;
        font-weight: bold;
        color: #1e3a5f;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .log-subject-name i {
        color: #0d6efd;
        font-size: 14px;
    }

    .log-source {
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .log-delta {
        font-weight: bold;
        font-size: 16px;
        font-family: 'Courier New', monospace;
    }
    .log-description {
        font-size: 12px;
        color: #6c757d;
        margin-top: 6px;
    }
    .log-time {
        font-size: 12px;
        color: #6c757d;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #adb5bd;
    }
    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.3;
    }

    /* ✅ دکمه‌های سریع تاریخ */
    .quick-dates {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .quick-dates .btn {
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        padding: 5px 12px;
    }

    /* ✅ استایل datepicker */
    .datepicker-plot-area {
        font-family: Tahoma, sans-serif !important;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">
            <i class="fas fa-history me-2 text-primary"></i>
            آخرین تغییرات موجودی
        </h4>
        <small class="text-muted">تمام تغییرات موجودی‌ها (دستی، ایمپورت، تولید، فروش و ...)</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteOldModal">
            <i class="fas fa-trash me-1"></i> پاک کردن لاگ‌های قدیمی
        </button>
        <a href="<?php echo e(route('inventory.index')); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> بازگشت به موجودی
        </a>
    </div>
</div>


<div class="log-filters">
    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'all']))); ?>"
       class="btn <?php echo e(request('type', 'all') === 'all' ? 'btn-dark active' : 'btn-outline-dark'); ?>">
        <i class="fas fa-list"></i>
        همه
        <span class="badge-count"><?php echo e(number_format($typeCounts['all'])); ?></span>
    </a>

    <?php
        $typeButtons = [
            'warehouse'    => ['label' => 'موجودی انبار',     'icon' => 'fa-warehouse',      'color' => 'primary'],
            'raw'          => ['label' => 'موجودی خام',       'icon' => 'fa-cube',           'color' => 'warning'],
            'wax'          => ['label' => 'موم',              'icon' => 'fa-fire',           'color' => 'danger'],
            'glaze1300'    => ['label' => '۱۳۰۰ درجه',        'icon' => 'fa-fire-alt',       'color' => 'dark'],
            'shoulder'     => ['label' => 'شانه شده',         'icon' => 'fa-bars',           'color' => 'info'],
            'waste_mum'    => ['label' => 'ضایعات موم',       'icon' => 'fa-trash',          'color' => 'secondary'],
            'raw_material' => ['label' => 'مواد اولیه',       'icon' => 'fa-flask',          'color' => 'success'],
            'packaging'    => ['label' => 'کارتن و لایه',     'icon' => 'fa-box',            'color' => 'info'],
        ];
    ?>

    <?php $__currentLoopData = $typeButtons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cfg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('type', 'page', 'quick'), ['type' => $key]))); ?>"
           class="btn <?php echo e(request('type') === $key ? 'btn-' . $cfg['color'] . ' active' : 'btn-outline-' . $cfg['color']); ?>">
            <i class="fas <?php echo e($cfg['icon']); ?>"></i>
            <?php echo e($cfg['label']); ?>

            <span class="badge-count"><?php echo e(number_format($typeCounts[$key])); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>


<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-center">
            <div class="col-md-2">
                <span class="fw-bold small text-muted">
                    <i class="fas fa-bolt text-warning me-1"></i>
                    میانبر تاریخ:
                </span>
            </div>
            <div class="col-md-10">
                <div class="quick-dates">
                    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'today']))); ?>"
                       class="btn <?php echo e($currentQuick === 'today' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="fas fa-calendar-day"></i> امروز
                    </a>
                    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'yesterday']))); ?>"
                       class="btn <?php echo e($currentQuick === 'yesterday' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="fas fa-calendar-minus"></i> دیروز
                    </a>
                    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'this_week']))); ?>"
                       class="btn <?php echo e($currentQuick === 'this_week' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="fas fa-calendar-week"></i> این هفته
                    </a>
                    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page'), ['quick' => 'this_month']))); ?>"
                       class="btn <?php echo e($currentQuick === 'this_month' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="fas fa-calendar-alt"></i> این ماه
                    </a>
                    <a href="<?php echo e(route('inventory-logs.index', array_merge(request()->except('quick', 'date_from', 'date_to', 'page')))); ?>"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> حذف فیلتر تاریخ
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form action="<?php echo e(route('inventory-logs.index')); ?>" method="GET" class="row g-3 align-items-end">
            <?php if(request('type')): ?>
                <input type="hidden" name="type" value="<?php echo e(request('type')); ?>">
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small fw-bold">از تاریخ</label>
                <input type="text"
                       name="date_from"
                       id="date_from"
                       class="form-control form-control-sm jalali-date-input"
                       value="<?php echo e($currentDateFrom); ?>"
                       placeholder="مثال: 1405/06/01"
                       autocomplete="off">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">تا تاریخ</label>
                <input type="text"
                       name="date_to"
                       id="date_to"
                       class="form-control form-control-sm jalali-date-input"
                       value="<?php echo e($currentDateTo); ?>"
                       placeholder="مثال: 1405/06/30"
                       autocomplete="off">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">منبع</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="all" <?php echo e(request('source', 'all') === 'all' ? 'selected' : ''); ?>>همه</option>
                    <option value="manual" <?php echo e(request('source') === 'manual' ? 'selected' : ''); ?>>دستی</option>
                    <option value="import" <?php echo e(request('source') === 'import' ? 'selected' : ''); ?>>ایمپورت اکسل</option>
                    <option value="production" <?php echo e(request('source') === 'production' ? 'selected' : ''); ?>>تولید</option>
                    <option value="tonneli" <?php echo e(request('source') === 'tonneli' ? 'selected' : ''); ?>>کوره تونلی</option>
                    <option value="shuttle" <?php echo e(request('source') === 'shuttle' ? 'selected' : ''); ?>>کوره شاتل</option>
                    <option value="sale" <?php echo e(request('source') === 'sale' ? 'selected' : ''); ?>>فروش</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">کاربر</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">همه</option>
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($u->id); ?>" <?php echo e(request('user_id') == $u->id ? 'selected' : ''); ?>>
                            <?php echo e($u->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold">جستجو</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="نام..." value="<?php echo e(request('search')); ?>">
            </div>

            <div class="col-md-12 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search me-1"></i> اعمال فیلتر
                </button>
                <a href="<?php echo e(route('inventory-logs.index', ['type' => request('type', 'all')])); ?>"
                   class="btn btn-secondary btn-sm">
                    <i class="fas fa-times me-1"></i> حذف همه فیلترها
                </a>
            </div>
        </form>
    </div>
</div>


<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if($logs->isEmpty()): ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h5>هیچ تغییری ثبت نشده است</h5>
                <p>به‌محض اینکه موجودی‌ها تغییر کنند، اینجا نمایش داده می‌شوند.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $delta = $log->delta;
                        $deltaClass = $delta > 0 ? 'positive' : ($delta < 0 ? 'negative' : 'neutral');
                    ?>
                    <div class="list-group-item log-row <?php echo e($deltaClass); ?>">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div class="flex-grow-1">

                                
                                <div class="mb-2">
                                    <span class="log-subject-name">
                                        <i class="fas fa-box-open"></i>
                                        <?php echo e($log->subject_name ?? '—'); ?>

                                    </span>
                                </div>

                                
                                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge <?php echo e($log->inventory_type_badge); ?>">
                                        <?php echo e($log->inventory_type_label); ?>

                                    </span>

                                    <?php if($log->source_label): ?>
                                        <span class="log-source text-primary">
                                            <i class="fas fa-arrow-left"></i>
                                            <?php echo e($log->source_label); ?>

                                        </span>
                                    <?php endif; ?>

                                    <?php if($log->user): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo e($log->user->name); ?>

                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if($log->description): ?>
                                    <div class="log-description">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <?php echo e($log->description); ?>

                                    </div>
                                <?php endif; ?>

                                <div class="log-time mt-2">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo e(\Morilog\Jalali\Jalalian::fromCarbon($log->created_at)->format('Y/m/d H:i:s')); ?>

                                </div>
                            </div>

                            <div class="text-start" style="min-width: 240px;">
                                <div class="d-flex gap-3 justify-content-end align-items-center">
                                    <div class="text-center">
                                        <div class="small text-muted">قبل</div>
                                        <div class="fw-bold"><?php echo e(number_format($log->old_value)); ?></div>
                                    </div>
                                    <i class="fas fa-arrow-left text-muted"></i>
                                    <div class="text-center">
                                        <div class="small text-muted">بعد</div>
                                        <div class="fw-bold"><?php echo e(number_format($log->new_value)); ?></div>
                                    </div>
                                    <div class="text-center">
                                        <div class="small text-muted">تغییر</div>
                                        <div class="log-delta text-<?php echo e($log->delta_color); ?>">
                                            <?php echo e($log->delta_label); ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <?php echo e($logs->links()); ?>

</div>


<div class="modal fade" id="deleteOldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo e(route('inventory-logs.delete-old')); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>
                        پاک کردن لاگ‌های قدیمی
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>هشدار:</strong> این عمل قابل بازگشت نیست!
                    </div>

                    <label class="form-label fw-bold">لاگ‌های قدیمی‌تر از:</label>
                    <select name="period" class="form-select" required>
                        <option value="1_month">۱ ماه پیش</option>
                        <option value="3_months">۳ ماه پیش</option>
                        <option value="6_months" selected>۶ ماه پیش</option>
                        <option value="1_year">۱ سال پیش</option>
                        <option value="all" class="text-danger">همه لاگ‌ها (پاک کردن کامل)</option>
                    </select>

                    <div class="form-text mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        لاگ‌های جدیدتر از بازه انتخابی حفظ می‌شوند.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('مطمئن هستید؟ این عمل قابل بازگشت نیست.');">
                        <i class="fas fa-trash me-1"></i> پاک کن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // ✅ راه‌اندازی date picker شمسی
    $(document).ready(function() {
        if (typeof $.fn.pDatepicker === 'undefined') {
            console.warn('[inventory-logs] persian-datepicker not loaded');
            return;
        }

        $('.jalali-date-input').pDatepicker({
            format: 'YYYY/MM/DD',
            initialValue: false,
            autoClose: true,
            persianDigit: false,
            observer: true,
            calendar: {
                persian: {
                    locale: 'fa',
                    leapYearMode: 'algorithmic'
                }
            },
            toolbox: {
                calendarSwitch: {
                    enabled: false
                }
            },
            navigator: {
                scroll: {
                    enabled: true
                }
            },
            timePicker: {
                enabled: false
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/inventory-logs/index.blade.php ENDPATH**/ ?>