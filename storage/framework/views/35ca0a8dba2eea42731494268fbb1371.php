<?php $__env->startSection('title', 'پخت‌های تونلی'); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(function() {
        $(document).on('change', '.packaged-toggle', function() {
            let checkbox = $(this);
            let url = checkbox.data('url');
            let badge = checkbox.closest('td').find('.packaged-badge');

            $.ajax({
                url: url,
                type: 'PATCH',
                data: { _token: '<?php echo e(csrf_token()); ?>' },
                success: function(r) {
                    if (r.success) {
                        if (r.is_packaged) {
                            badge.removeClass('bg-danger').addClass('bg-success').text('بله');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-danger').text('خیر');
                        }
                    }
                }
            });
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">پخت‌های کوره تونلی</h4>
    <a href="<?php echo e(route('tonneli.create')); ?>" class="btn btn-primary"><i class="fas fa-plus me-1"></i> ثبت جدید</a>
</div>

<?php if(session('success')): ?>
    <div class="alert alert-success"><?php echo e(session('success')); ?></div>
<?php endif; ?>

<table class="table table-hover align-middle bg-white rounded shadow-sm">
    <thead class="table-light">
        <tr>
            <th>تاریخ</th>
            <th>محصولات</th>
            <th>عملیات</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $firings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $firing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr>
            <td><?php echo e($firing->jalali_date ?? '—'); ?></td>
            <td>
                <?php $__currentLoopData = $firing->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="badge bg-secondary"><?php echo e($item->product->name ?? '—'); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </td>
            <td class="d-flex gap-1">
                <a href="<?php echo e(url('/tonneli/'.$firing->id)); ?>" class="btn btn-sm btn-outline-info" title="مشاهده"><i class="fas fa-eye"></i></a>
                <a href="<?php echo e(url('/tonneli/'.$firing->id.'/edit')); ?>" class="btn btn-sm btn-outline-warning" title="ویرایش"><i class="fas fa-edit"></i></a>
                <form action="<?php echo e(route('tonneli.destroy', $firing->id)); ?>" method="POST" onsubmit="return confirm('مطمئن هستید؟')">
                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                    <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="6" class="text-center">هیچ رکوردی یافت نشد.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="mt-3"><?php echo e($firings->links()); ?></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\ceramic-erp-backup\resources\views/tonneli/index.blade.php ENDPATH**/ ?>