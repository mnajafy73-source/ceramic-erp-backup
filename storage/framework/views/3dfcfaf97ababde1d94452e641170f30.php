<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'پنل مدیریت کارخانه سرامیک'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        :root {
            --primary: #1e3a5f;
            --secondary: #2b5f8e;
            --light-bg: #f0f4f8;
            --sidebar-width: 260px;
        }
        body {
            background-color: var(--light-bg);
            font-family: Tahoma, sans-serif;
            margin: 0;
        }

        /* ✅ Sidebar: به صورت پیش‌فرض بسته */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            z-index: 1050;
            box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            overflow-y: auto;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            position: relative;
        }
        .sidebar .sidebar-header .sidebar-close-btn {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .sidebar .sidebar-header .sidebar-close-btn:hover {
            background: rgba(255,255,255,0.25);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 0.8rem 1.5rem;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            text-align: right;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            border-left-color: #ffc107;
            color: white !important;
        }
        .sidebar .submenu {
            display: none;
            background: rgba(0, 0, 0, 0.25);
        }
        .sidebar .submenu.open {
            display: block;
        }
        .sidebar .submenu a {
            display: block;
            padding: 10px 40px 10px 20px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s;
        }
        .sidebar .submenu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .menu-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sidebar .menu-arrow {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }
        .sidebar .menu-title.open .menu-arrow {
            transform: rotate(180deg);
        }

        .main-content {
            margin-right: 0;
            min-height: 100vh;
        }

        .topbar {
            background: white;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .menu-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            font-size: 1.6rem;
            color: var(--primary);
            cursor: pointer;
            padding: 4px 10px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .menu-toggle:hover {
            background: #f0f4f8;
        }

        .alert-undo {
            background: #e8f5fe;
            border: 1px solid #b8dfff;
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .alert-undo .btn-group {
            display: flex;
            gap: 8px;
        }

        /* ✅ Overlay لودینگ واردات */
        #importOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 9999;
            color: white;
            text-align: center;
            padding-top: 18vh;
        }
        #importOverlay .spinner-border {
            width: 5rem;
            height: 5rem;
            border-width: 0.5rem;
        }
        #importOverlay h4 {
            margin-top: 2rem;
            font-weight: bold;
        }
        #importOverlay p {
            margin-top: 1rem;
            opacity: 0.85;
        }
        .import-progress-wrapper {
            margin-top: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .import-progress-wrapper .progress {
            height: 12px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        .import-progress-wrapper .progress-bar {
            background: linear-gradient(90deg, #28a745, #ffc107);
            transition: width 0.4s ease;
        }

        /* ✅ Toast پیام */
        .toast-container-custom {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            max-width: 90%;
            width: 500px;
        }

        /* ✅ Overlay پشت sidebar */
        #sidebarOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1040;
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>

    
    <div id="importOverlay">
        <div class="spinner-border text-warning" role="status"></div>
        <h4>در حال واردات خودکار از فایل اکسل...</h4>
        <p>لطفاً این پنجره را نبندید و صبر کنید تا عملیات کامل شود.</p>

        <div class="import-progress-wrapper">
            <div class="progress">
                <div class="progress-bar" id="importProgressBar" style="width: 0%;"></div>
            </div>
        </div>
    </div>

    
    <div class="toast-container-custom" id="toastContainer"></div>

    
    <div id="sidebarOverlay" onclick="closeSidebar()"></div>

    
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="fas fa-industry ms-2"></i>کارخانه سرامیک</h5>
            <small class="text-white-50">پنل مدیریت</small>

            <button type="button" class="sidebar-close-btn" onclick="closeSidebar()" title="بستن منو">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="pt-3">
            <!-- ۱. داشبورد -->
            <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                <i class="fas fa-tachometer-alt"></i> داشبورد
            </a>

            <!-- ۲. خرید -->
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-purchases')">
                <span><i class="fas fa-shopping-basket"></i> خرید</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-purchases">
                <a href="<?php echo e(route('raw-material-purchases.index')); ?>"><i class="fas fa-cube"></i> خرید مواد</a>
                <a href="<?php echo e(route('packaging-purchases.index')); ?>"><i class="fas fa-box"></i> خرید کارتن و لایه</a>
            </div>

            <!-- ۳. تولید -->
            <a href="<?php echo e(route('productions.index')); ?>" class="nav-link <?php echo e(request()->routeIs('productions.*') ? 'active' : ''); ?>">
                <i class="fas fa-industry"></i> تولید
            </a>

            <!-- ۴. مواد سازی -->
            <a href="<?php echo e(route('material-making.index')); ?>" class="nav-link <?php echo e(request()->routeIs('material-making.*') ? 'active' : ''); ?>">
                <i class="fas fa-flask"></i> مواد سازی
            </a>

            <!-- ۵. کوره‌ها -->
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-kilns')">
                <span><i class="fas fa-fire"></i> کوره‌ها</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-kilns">
                <a href="<?php echo e(route('tonneli.index')); ?>"><i class="fas fa-industry"></i> پخت کوره تونلی</a>
                <a href="<?php echo e(route('shuttle.index')); ?>"><i class="fas fa-train"></i> پخت شاتل</a>
            </div>

            <!-- ۶. فروش -->
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-sales')">
                <span><i class="fas fa-shopping-cart"></i> فروش</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-sales">
                <a href="<?php echo e(route('sales.index')); ?>"><i class="fas fa-file-invoice-dollar"></i> رسمی</a>
                <a href="<?php echo e(route('informal-sales.index')); ?>"><i class="fas fa-file-invoice"></i> غیررسمی</a>
                <a href="<?php echo e(route('cost-price.index')); ?>"><i class="fas fa-calculator"></i> قیمت تمام شده</a>
            </div>

            <!-- ۷. موجودی -->
            <button class="nav-link menu-title <?php echo e(request()->routeIs('inventory.*') || request()->routeIs('inventory-logs.*') ? 'active' : ''); ?>"
                    onclick="toggleSubmenu(this, 'submenu-inventory')">
                <span><i class="fas fa-cubes"></i> موجودی</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-inventory">
                <a href="<?php echo e(route('inventory.raw-materials')); ?>"><i class="fas fa-cube"></i> مواد اولیه</a>
                <a href="<?php echo e(route('inventory.packaging-stock')); ?>"><i class="fas fa-box"></i> کارتن و لایه</a>
                <a href="<?php echo e(route('inventory.warehouse')); ?>"><i class="fas fa-warehouse"></i> موجودی انبار</a>
                <a href="<?php echo e(route('inventory.all-stocks')); ?>"><i class="fas fa-chart-pie"></i> گزارش جامع موجودی‌ها</a>
                <a href="<?php echo e(route('inventory-logs.index')); ?>"><i class="fas fa-history"></i> آخرین تغییرات</a>
            </div>

            <!-- ۸. گزارشات -->
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-reports')">
                <span><i class="fas fa-chart-bar"></i> گزارشات</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-reports">
                <a href="<?php echo e(route('reports.production')); ?>"><i class="fas fa-industry"></i> گزارش تولید</a>
                <a href="<?php echo e(route('reports.firing')); ?>"><i class="fas fa-fire"></i> گزارش پخت</a>
                <a href="<?php echo e(route('reports.annual')); ?>"><i class="fas fa-calendar-alt"></i> گزارش سالیانه</a>
            </div>

            <!-- ۹. آمار -->
            <a href="<?php echo e(route('product-sales-stats.index')); ?>" class="nav-link <?php echo e(request()->routeIs('product-sales-stats.*') ? 'active' : ''); ?>">
                <i class="fas fa-chart-line"></i> آمار
            </a>

            <!-- ۱۰. وارد کردن -->
            <form method="POST" action="<?php echo e(route('import.from-path')); ?>" id="sidebarImportForm" class="m-0 p-0">
                <?php echo csrf_field(); ?>
                <button type="submit" class="nav-link <?php echo e(request()->routeIs('import.*') ? 'active' : ''); ?>" id="sidebarImportBtn">
                    <i class="fas fa-upload"></i> وارد کردن
                </button>
            </form>

            <hr class="text-white-50 mx-3 my-2">

            <!-- ۱۱. تنظیمات -->
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-settings')">
                <span><i class="fas fa-cog"></i> تنظیمات</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-settings">
                <div style="padding: 5px 40px 5px 20px; color: rgba(255,255,255,0.4); font-size: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05);">تعاریف پایه</div>
                <a href="<?php echo e(route('raw-materials.index')); ?>"><i class="fas fa-cube"></i> مواد اولیه</a>
                <a href="<?php echo e(route('formulas.index')); ?>"><i class="fas fa-calculator"></i> فرمول‌ها</a>
                <a href="<?php echo e(route('packagings.index')); ?>"><i class="fas fa-box"></i> کارتن و لایه</a>

                <div style="padding: 5px 40px 5px 20px; color: rgba(255,255,255,0.4); font-size: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05);">مدیریت سیستم</div>
                <a href="<?php echo e(route('operators.index')); ?>"><i class="fas fa-user-cog"></i> مدیریت اپراتورها</a>
                <a href="<?php echo e(route('presses.index')); ?>"><i class="fas fa-cogs"></i> مدیریت پرس‌ها</a>
                <a href="<?php echo e(route('products.index')); ?>"><i class="fas fa-box"></i> مدیریت کالاها</a>
                <a href="<?php echo e(route('customers.index')); ?>"><i class="fas fa-users"></i> مدیریت مشتریان</a>
                <a href="<?php echo e(route('product_logs.index')); ?>"><i class="fas fa-history"></i> تاریخچه تغییرات کالاها</a>
            </div>

            <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-3 px-3 pb-3">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-outline-light btn-sm w-100">
                    <i class="fas fa-sign-out-alt ms-1"></i> خروج
                </button>
            </form>
        </div>
    </nav>

    <div class="main-content" id="mainContent">
        <div class="topbar">
            <button class="menu-toggle" id="menuToggle" onclick="openSidebar()" title="باز کردن منو">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <span class="text-muted"><?php echo e(Auth::user()->name ?? 'کاربر'); ?></span>
            </div>
        </div>
        <div class="p-3 p-md-4">

            <?php if(session('undo_record')): ?>
                <div class="alert-undo">
                    <span>
                        <i class="fas fa-undo-alt me-2 text-primary"></i>
                        یک عملیات حذف قابل برگشت است.
                    </span>
                    <div class="btn-group">
                        <a href="<?php echo e(route('undo.restore')); ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-undo me-1"></i> بازگرداندن
                        </a>
                        <a href="<?php echo e(route('undo.discard')); ?>" class="btn btn-sm btn-danger">
                            <i class="fas fa-times me-1"></i> لغو
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            sidebarOverlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            sidebarOverlay.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });

        function toggleSubmenu(button, submenuId) {
            event.preventDefault();
            const submenu = document.getElementById(submenuId);
            submenu.classList.toggle('open');
            button.classList.toggle('open');
        }

        // ═══════════════════════════════════════════════════════════
        //  نمایش Toast پیام
        // ═══════════════════════════════════════════════════════════
        function showToast(message, type) {
            var container = document.getElementById('toastContainer');
            var isSuccess = (type === 'success');

            var bgColor     = isSuccess ? '#d1e7dd' : '#f8d7da';
            var borderColor = isSuccess ? '#badbcc' : '#f5c2c7';
            var textColor   = isSuccess ? '#0a3622' : '#58151c';
            var iconColor   = isSuccess ? '#198754' : '#dc3545';
            var iconClass   = isSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle';

            var alert = document.createElement('div');
            alert.setAttribute('role', 'alert');
            alert.style.cssText =
                'background-color: ' + bgColor + ' !important; ' +
                'border: 1px solid ' + borderColor + ' !important; ' +
                'color: ' + textColor + ' !important; ' +
                'border-radius: 10px; ' +
                'padding: 14px 18px; ' +
                'margin-bottom: 10px; ' +
                'box-shadow: 0 4px 20px rgba(0,0,0,0.3); ' +
                'position: relative; ' +
                'display: flex; align-items: flex-start; gap: 10px;';

            alert.innerHTML =
                '<i class="fas ' + iconClass + '" style="color: ' + iconColor + ' !important; font-size: 20px; margin-top: 2px;"></i>' +
                '<div style="flex: 1; color: ' + textColor + ' !important; line-height: 1.9; font-size: 14px; white-space: pre-wrap;">' +
                    message +
                '</div>' +
                '<button type="button" ' +
                    'style="background: none; border: none; font-size: 20px; line-height: 1; cursor: pointer; color: ' + textColor + ' !important; opacity: 0.5; padding: 0; margin-right: 4px;" ' +
                    'onclick="this.parentNode.remove();">&times;</button>';

            container.appendChild(alert);

            setTimeout(function() {
                if (alert.parentNode) {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        if (alert.parentNode) alert.remove();
                    }, 500);
                }
            }, 8000);
        }

        <?php if(session('import_result')): ?>
            (function() {
                var ir = <?php echo json_encode(session('import_result'), 15, 512) ?>;
                if (ir && ir.message) {
                    showToast(ir.message, ir.type === 'success' ? 'success' : 'error');
                }
            })();
        <?php endif; ?>

        // ═══════════════════════════════════════════════════════════
        //  واردات خودکار - فرم POST عادی
        // ═══════════════════════════════════════════════════════════
        var importFormConfirmed = false;

        var importForm = document.getElementById('sidebarImportForm');
        if (importForm) {
            importForm.addEventListener('submit', function(e) {
                if (importFormConfirmed) {
                    return true;
                }

                if (!confirm('آیا از شروع واردات خودکار مطمئن هستید؟\n\nتمام داده‌های تولید، کوره، فروش و مواد سازی از فایل اکسل بازنویسی می‌شوند.')) {
                    e.preventDefault();
                    return false;
                }

                document.getElementById('importOverlay').style.display = 'block';

                var pbar = document.getElementById('importProgressBar');
                var pct = 0;
                var pInterval = setInterval(function() {
                    pct += Math.random() * 4;
                    if (pct > 95) pct = 95;
                    pbar.style.width = pct + '%';
                }, 500);

                var btn = document.getElementById('sidebarImportBtn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال واردات...';

                importFormConfirmed = true;
            });
        }
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH F:\ceramic-erp-backup\resources\views/layouts/app.blade.php ENDPATH**/ ?>