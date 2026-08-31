<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت کارخانه سرامیک')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        /* ✅ استایل بخش Undo */
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
        }
    </style>
    @stack('styles')
</head>
<body>

    <div id="sidebarOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1040;" onclick="closeSidebar()"></div>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0"><i class="fas fa-industry ms-2"></i>کارخانه سرامیک</h5>
            <small class="text-white-50">پنل مدیریت</small>
        </div>
        <div class="pt-3">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> داشبورد
            </a>

            <a href="{{ route('productions.index') }}" class="nav-link {{ request()->routeIs('productions.*') ? 'active' : '' }}">
                <i class="fas fa-industry"></i> تولید
            </a>

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-kilns')">
                <span><i class="fas fa-fire"></i> کوره‌ها</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-kilns">
                <a href="{{ route('tonneli.index') }}"><i class="fas fa-industry"></i> پخت کوره تونلی</a>
                <a href="{{ route('shuttle.index') }}"><i class="fas fa-train"></i> پخت شاتل</a>
            </div>

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-sales')">
                <span><i class="fas fa-shopping-cart"></i> فروش</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-sales">
                <a href="{{ route('sales.index') }}"><i class="fas fa-file-invoice-dollar"></i> رسمی</a>
                <a href="{{ route('informal-sales.index') }}"><i class="fas fa-file-invoice"></i> غیررسمی</a>
                <a href="{{ route('cost-price.index') }}"><i class="fas fa-calculator"></i> قیمت تمام شده</a>
            </div>

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-purchases')">
                <span><i class="fas fa-shopping-basket"></i> خرید</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-purchases">
                <a href="{{ route('raw-material-purchases.index') }}"><i class="fas fa-cube"></i> خرید مواد</a>
                <a href="{{ route('packaging-purchases.index') }}"><i class="fas fa-box"></i> خرید کارتن و لایه</a>
            </div>

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-inventory')">
                <span><i class="fas fa-cubes"></i> موجودی</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-inventory">
                <a href="{{ route('opening-inventories.index') }}"><i class="fas fa-database"></i> موجودی اول دوره</a>
                <a href="{{ route('inventory.raw-materials') }}"><i class="fas fa-cube"></i> مواد اولیه</a>
                <a href="{{ route('inventory.raw') }}"><i class="fas fa-cube"></i> موجودی خام</a>
                <a href="{{ route('inventory.mum') }}"><i class="fas fa-fire"></i> موجودی موم (۹۰۰°)</a>
                <a href="{{ route('inventory.glaze1300') }}"><i class="fas fa-fire"></i> موجودی ۱۳۰۰°</a>
                <a href="{{ route('inventory.packaging-stock') }}"><i class="fas fa-box"></i> کارتن و لایه</a>
                <a href="{{ route('inventory.warehouse') }}"><i class="fas fa-warehouse"></i> موجودی انبار</a>
            </div>

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-reports')">
                <span><i class="fas fa-chart-bar"></i> گزارشات</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-reports">
                <a href="{{ route('reports.production') }}"><i class="fas fa-industry"></i> گزارش تولید</a>
                <a href="{{ route('reports.firing') }}"><i class="fas fa-fire"></i> گزارش پخت</a>
                <a href="{{ route('reports.annual') }}"><i class="fas fa-calendar-alt"></i> گزارش سالیانه</a>
            </div>

            <a href="{{ route('import.index') }}" class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}">
                <i class="fas fa-upload"></i> وارد کردن
            </a>

            <hr class="text-white-50 mx-3 my-2">

            <button class="nav-link menu-title" onclick="toggleSubmenu(this, 'submenu-settings')">
                <span><i class="fas fa-cog"></i> تنظیمات</span>
                <i class="fas fa-chevron-down menu-arrow"></i>
            </button>
            <div class="submenu" id="submenu-settings">
                <div style="padding: 5px 40px 5px 20px; color: rgba(255,255,255,0.4); font-size: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05);">تعاریف پایه</div>
                <a href="{{ route('raw-materials.index') }}"><i class="fas fa-cube"></i> مواد اولیه</a>
                <a href="{{ route('formulas.index') }}"><i class="fas fa-calculator"></i> فرمول‌ها</a>
                <a href="{{ route('packagings.index') }}"><i class="fas fa-box"></i> کارتن و لایه</a>

                <div style="padding: 5px 40px 5px 20px; color: rgba(255,255,255,0.4); font-size: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.05);">مدیریت سیستم</div>
                <a href="{{ route('operators.index') }}"><i class="fas fa-user-cog"></i> مدیریت اپراتورها</a>
                <a href="{{ route('presses.index') }}"><i class="fas fa-cogs"></i> مدیریت پرس‌ها</a>
                <a href="{{ route('products.index') }}"><i class="fas fa-box"></i> مدیریت کالاها</a>
                <a href="{{ route('customers.index') }}"><i class="fas fa-users"></i> مدیریت مشتریان</a>
                <a href="{{ route('product_logs.index') }}"><i class="fas fa-history"></i> تاریخچه تغییرات</a>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3 px-3 pb-3">
                @csrf
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
                <span class="text-muted">{{ Auth::user()->name ?? 'کاربر' }}</span>
            </div>
        </div>
        <div class="p-3 p-md-4">

            {{-- ============================================================ --}}
            {{--  ✅ بخش Undo (بازگرداندن) - مستقل از سایر پیام‌ها  --}}
            {{-- ============================================================ --}}
            @if(session('undo_record'))
                <div class="alert-undo">
                    <span>
                        <i class="fas fa-undo-alt me-2 text-primary"></i>
                        یک عملیات حذف قابل برگشت است.
                    </span>
                    <div class="btn-group">
                        <a href="{{ route('undo.restore') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-undo me-1"></i> بازگرداندن
                        </a>
                        <a href="{{ route('undo.discard') }}" class="btn btn-sm btn-danger">
                            <i class="fas fa-times me-1"></i> لغو
                        </a>
                    </div>
                </div>
            @endif

            {{-- ============================================================ --}}
            {{--  نمایش پیام‌های موفقیت و خطا  --}}
            {{-- ============================================================ --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
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
    </script>
    @stack('scripts')
</body>
</html>