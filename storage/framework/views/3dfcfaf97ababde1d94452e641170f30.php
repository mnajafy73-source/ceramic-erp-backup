<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            transform: translateX(0);
            transition: transform 0.4s;
        }
        .sidebar .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
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
            margin-right: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-right 0.4s;
        }
        .topbar {
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--primary);
            cursor: pointer;
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

        /* ✅ Overlay لودینگ واردات با تایمر شمارش معکوس */
        #importOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
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
            opacity: 0.8;
        }
        .countdown-box {
            margin-top: 2rem;
            display: inline-block;
            padding: 18px 40px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid #ffc107;
            border-radius: 16px;
            min-width: 260px;
        }
        .countdown-label {
            font-size: 14px;
            opacity: 0.85;
            margin-bottom: 8px;
        }
        .countdown-time {
            font-size: 52px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            color: #ffc107;
            letter-spacing: 3px;
            direction: ltr;
            line-height: 1.1;
        }
        .countdown-finishing {
            font-size: 22px;
            font-weight: bold;
            color: #28a745;
            margin-top: 10px;
            display: none;
        }
        .progress-wrapper {
            margin-top: 20px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .progress-wrapper .progress {
            height: 10px;
            background: rgba(255,255,255,0.1);
        }
        .progress-wrapper .progress-bar {
            background: linear-gradient(90deg, #28a745, #ffc107);
            transition: width 1s linear;
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
        .toast-container-custom .alert {
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-right: 0;
            }
            .menu-toggle {
                display: block;
            }
            .countdown-time { font-size: 40px; }
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>

    
    <div id="importOverlay">
        <div class="spinner-border text-warning" role="status"></div>
        <h4>در حال واردات خودکار از فایل اکسل...</h4>
        <p>لطفاً این پنجره را نبندید.</p>

        <div class="countdown-box">
            <div class="countdown-label">
                <i class="fas fa-hourglass-half me-1"></i>
                زمان تقریبی باقی‌مانده:
            </div>
            <div class="countdown-time" id="countdownTime">02:00</div>
            <div class="countdown-finishing" id="countdownFinishing">
                <i class="fas fa-check-circle me-1"></i>
                در حال آماده‌سازی صفحه...
            </div>
        </div>

        <div class="progress-wrapper">
            <div class="progress">
                <div class="progress-bar" id="importProgressBar" style="width: 0%;"></div>
            </div>
        </div>
    </div>

    
    <div class="toast-container-custom" id="toastContainer"></div>

    <div id="sidebarOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1040;" onclick="closeSidebar()"></div>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="fas fa-industry ms-2"></i>کارخانه سرامیک</h5>
            <small class="text-white-50">پنل مدیریت</small>
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
            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-inventory')">
                <span><i class="fas fa-cubes"></i> موجودی</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-inventory">
                <a href="<?php echo e(route('inventory.raw-materials')); ?>"><i class="fas fa-cube"></i> مواد اولیه</a>
                <a href="<?php echo e(route('inventory.packaging-stock')); ?>"><i class="fas fa-box"></i> کارتن و لایه</a>
                <a href="<?php echo e(route('inventory.warehouse')); ?>"><i class="fas fa-warehouse"></i> موجودی انبار</a>
                <a href="<?php echo e(route('inventory.all-stocks')); ?>"><i class="fas fa-chart-pie"></i> گزارش جامع موجودی‌ها</a>
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
                <a href="<?php echo e(route('product_logs.index')); ?>"><i class="fas fa-history"></i> تاریخچه تغییرات</a>
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
            <button class="menu-toggle" id="menuToggle" onclick="openSidebar()">
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
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.style.display = 'block';
        }
        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.style.display = 'none';
        }
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('active');
                overlay.style.display = 'none';
            }
        });

        function toggleSubmenu(button, submenuId) {
            event.preventDefault();
            const submenu = document.getElementById(submenuId);
            submenu.classList.toggle('open');
            button.classList.toggle('open');
        }

        // ✅ نمایش Toast پیام
        function showToast(message, type) {
            const container = document.getElementById('toastContainer');
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show';
            alert.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle') + ' me-2"></i>' + message +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            container.appendChild(alert);
            setTimeout(function() {
                if (alert.parentNode) alert.remove();
            }, 8000);
        }

        // ============================================================
        //  ✅ واردات خودکار با تایمر شمارش معکوس
        // ============================================================

        // ⏱️ زمان تقریبی واردات (به ثانیه) — هر وقت خواستی تغییرش بده
        const IMPORT_ESTIMATED_SECONDS = 120; // 2 دقیقه

        // ⚡ زمان سریع برای اتمام نمایش پس از آماده شدن پاسخ (به ثانیه)
        const FAST_FINISH_SECONDS = 3;

        var countdownInterval = null;
        var secondsRemaining = IMPORT_ESTIMATED_SECONDS;
        var ajaxCompleted = false;
        var ajaxResult = null;

        function formatTime(totalSeconds) {
            if (totalSeconds < 0) totalSeconds = 0;
            var m = Math.floor(totalSeconds / 60);
            var s = totalSeconds % 60;
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function updateCountdownDisplay() {
            document.getElementById('countdownTime').textContent = formatTime(secondsRemaining);
            var pct = ((IMPORT_ESTIMATED_SECONDS - secondsRemaining) / IMPORT_ESTIMATED_SECONDS) * 100;
            if (pct < 0) pct = 0;
            if (pct > 100) pct = 100;
            document.getElementById('importProgressBar').style.width = pct + '%';
        }

        function startCountdown() {
            secondsRemaining = IMPORT_ESTIMATED_SECONDS;
            ajaxCompleted = false;
            ajaxResult = null;
            document.getElementById('countdownFinishing').style.display = 'none';
            document.getElementById('countdownTime').style.display = 'block';
            updateCountdownDisplay();

            if (countdownInterval) clearInterval(countdownInterval);

            countdownInterval = setInterval(function() {
                // اگه AJAX تموم شده و داریم سریع می‌ریم به سمت صفر
                if (ajaxCompleted && secondsRemaining > FAST_FINISH_SECONDS) {
                    secondsRemaining = FAST_FINISH_SECONDS;
                } else if (secondsRemaining > 0) {
                    secondsRemaining--;
                } else {
                    // شمارش معکوس رسید به صفر
                    if (ajaxCompleted) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                        finishImport();
                    } else {
                        // AJAX هنوز تموم نشده، نگه دار روی صفر و پیام بده
                        if (document.getElementById('countdownFinishing').style.display === 'none') {
                            document.getElementById('countdownTime').style.display = 'none';
                            document.getElementById('countdownFinishing').innerHTML =
                                '<i class="fas fa-hourglass-end me-1"></i> در حال اتمام، لطفاً کمی صبر کنید...';
                            document.getElementById('countdownFinishing').style.display = 'block';
                        }
                    }
                }
                updateCountdownDisplay();
            }, 1000);
        }

        function finishImport() {
            if (ajaxResult) {
                if (ajaxResult.status === 'success') {
                    // اگه پیام موفقیت داره، اول نشون بده بعد رفرش کن
                    if (ajaxResult.message) {
                        showToast(ajaxResult.message, 'success');
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 800);
                } else {
                    // خطا — Overlay رو ببند و پیام خطا نشون بده
                    document.getElementById('importOverlay').style.display = 'none';
                    showToast(ajaxResult.message || 'خطا در واردات', 'error');
                    document.getElementById('sidebarImportBtn').disabled = false;
                    document.getElementById('sidebarImportBtn').innerHTML = '<i class="fas fa-upload"></i> وارد کردن';
                }
            }
        }

        document.getElementById('sidebarImportForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!confirm('آیا از شروع واردات خودکار مطمئن هستید؟\n\nتمام داده‌های تولید، کوره، فروش و مواد سازی از فایل اکسل بازنویسی می‌شوند.')) {
                return;
            }

            const overlayEl = document.getElementById('importOverlay');
            const btn = document.getElementById('sidebarImportBtn');

            overlayEl.style.display = 'block';
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال واردات...';

            // ⏱️ شروع شمارش معکوس
            startCountdown();

            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                ajaxCompleted = true;
                ajaxResult = data;

                // اگه شمارش معکوس کمتر از FAST_FINISH_SECONDS مونده، سریع ادامه بده
                if (secondsRemaining > FAST_FINISH_SECONDS) {
                    secondsRemaining = FAST_FINISH_SECONDS;
                }

            } catch (error) {
                ajaxCompleted = true;
                ajaxResult = { status: 'error', message: 'خطا در ارتباط با سرور: ' + error.message };
                if (secondsRemaining > FAST_FINISH_SECONDS) {
                    secondsRemaining = FAST_FINISH_SECONDS;
                }
            }
        });
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH F:\ceramic-erp-backup\resources\views/layouts/app.blade.php ENDPATH**/ ?>