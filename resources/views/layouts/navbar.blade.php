@php
    $navUserModules = auth()->user()->role?->modules()->pluck('name')->toArray() ?? [];
    $navHasDashboardAccess = in_array('Reports', $navUserModules);
    $navHasInventoryAccess = in_array('Inventory & Products', $navUserModules);
    $navHasSettingsAccess = in_array('Settings', $navUserModules);
    // No Dashboard for this role — Sales Report is the closest thing a
    // Cashier has to a "home" page, so send the logo there instead of a
    // dead link.
    $navHasSalesReportAccess = in_array('Sales Report', $navUserModules);
@endphp
<nav class="navbar fixed top-0 left-0 right-0 z-50">
    <div class="navbar-inner">
        <!-- Logo -->
        <div class="flex items-center gap-3">
            <button type="button" class="navbar-hamburger lg:hidden" onclick="toggleMobileSidebar()" title="Menu">
                <i class="fas fa-bars"></i>
            </button>
            @if($navHasDashboardAccess)
                <a href="{{ route('dashboard') }}" class="navbar-logo-wrap">
                    <img src="{{ asset('images/jaan_logo.jpg') }}" alt="Logo" class="navbar-logo">
                </a>
            @elseif($navHasSalesReportAccess)
                <a href="{{ route('sales-report.index') }}" class="navbar-logo-wrap">
                    <img src="{{ asset('images/jaan_logo.jpg') }}" alt="Logo" class="navbar-logo">
                </a>
            @else
                <a href="javascript:void(0)" class="navbar-logo-wrap" onclick="navNoAccessToast()">
                    <img src="{{ asset('images/jaan_logo.jpg') }}" alt="Logo" class="navbar-logo">
                </a>
            @endif
        </div>

        <!-- Right actions -->
        <div class="navbar-actions">
            <!-- Current page label -->
            <span class="hidden md:block" style="font-size:12px; color:#64748b; font-weight:500; margin-right:6px;">
                {{ request()->routeIs('dashboard') || request()->routeIs('reports.index') || request()->routeIs('reports.sales') ? 'Dashboard' : (request()->segment(1) ? ucfirst(str_replace('-', ' ', request()->segment(1))) : '') }}
            </span>

            <div class="nav-divider hidden sm:block"></div>

            <!-- Low stock bell -->
            @if($navHasInventoryAccess)
                <a href="{{ route('inventory.dashboard') }}" class="nav-bell" title="{{ $lowStockCount > 0 ? $lowStockCount . ' low stock alert(s)' : 'Inventory' }}">
                    <i class="fas fa-bell" style="font-size:16px; color:{{ $lowStockCount > 0 ? '#f59e0b' : '#64748b' }};"></i>
                    @if($lowStockCount > 0)
                        <span class="nav-bell-badge">{{ $lowStockCount > 99 ? '99+' : $lowStockCount }}</span>
                    @endif
                </a>
            @else
                <a href="javascript:void(0)" class="nav-bell" title="Inventory" onclick="navNoAccessToast()">
                    <i class="fas fa-bell" style="font-size:16px; color:#64748b;"></i>
                </a>
            @endif

            <!-- Dark mode toggle -->
            <button type="button" class="dm-toggle" onclick="toggleDarkMode()" title="Toggle dark mode">
                <i class="fas fa-moon" id="dmToggleIcon"></i>
            </button>

            <div class="nav-divider hidden sm:block"></div>

            <!-- User pill + dropdown -->
            <div style="position:relative;">
                <button class="nav-user-pill" onclick="toggleDropdown()" type="button">
                    <div class="nav-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="hidden sm:block">
                        <div class="nav-user-name">{{ auth()->user()->name ?? 'User' }}</div>
                        <div class="nav-user-role">{{ auth()->user()->role->name ?? 'User' }}</div>
                    </div>
                    <i class="fas fa-chevron-down hidden sm:block" style="font-size:10px; color:#64748b; margin-left:4px;"></i>
                </button>

                <div id="dropdown" class="nav-dropdown">
                    <div style="padding:12px 16px 8px; border-bottom:1px solid rgba(255,255,255,0.08);">
                        <div style="font-size:13px; font-weight:700; color:#f1f5f9;">{{ auth()->user()->name ?? 'User' }}</div>
                        <div style="font-size:11px; color:#64748b; margin-top:2px;">{{ auth()->user()->email ?? '' }}</div>
                    </div>
                    @if($navHasDashboardAccess)
                        <a href="{{ route('dashboard') }}">
                            <i class="fas fa-gauge-high" style="width:16px;"></i> Dashboard
                        </a>
                    @else
                        <a href="javascript:void(0)" onclick="navNoAccessToast()">
                            <i class="fas fa-gauge-high" style="width:16px;"></i> Dashboard
                        </a>
                    @endif
                    @if($navHasSettingsAccess)
                        <a href="{{ route('settings.index') }}">
                            <i class="fas fa-gear" style="width:16px;"></i> Settings
                        </a>
                    @else
                        <a href="javascript:void(0)" onclick="navNoAccessToast()">
                            <i class="fas fa-gear" style="width:16px;"></i> Settings
                        </a>
                    @endif
                    <hr>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dd-danger">
                            <i class="fas fa-right-from-bracket" style="width:16px;"></i> Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    /* ── Navbar ── */
    .navbar {
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        border-bottom: 1px solid #eef1f6;
        height: 64px;
        flex-shrink: 0;
        width: 100%;
        position: fixed;
        top: 0; left: 0; right: 0; z-index: 50;
    }
    .navbar-inner {
        display: flex; align-items: center; justify-content: space-between;
        height: 100%; padding: 0 24px;
    }
    .navbar-hamburger {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 10px; background: #f8fafc;
        border: 1px solid #eef1f6; color: #475569; cursor: pointer; font-size: 15px;
    }
    .navbar-hamburger:hover { background: #eff6ff; color: #2563eb; }
    .navbar-logo-wrap {
        display: flex; align-items: center; text-decoration: none; transition: opacity 0.2s;
    }
    .navbar-logo-wrap:hover { opacity: 0.85; }
    .navbar-logo {
        height: 40px; width: auto; max-width: 160px; object-fit: contain; display: block; border-radius: 5px;
    }
    .navbar-actions { display: flex; align-items: center; gap: 8px; }
    .nav-divider { width: 1px; height: 28px; background: #eef1f6; margin: 0 4px; }
    .nav-user-pill {
        display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #eef1f6;
        border-radius: 50px; padding: 5px 14px 5px 6px; cursor: pointer; transition: all 0.18s; outline: none; text-decoration: none; color: inherit;
    }
    .nav-user-pill:hover { background: #eff6ff; border-color: #dbeafe; }
    .nav-user-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1d4ed8);
        display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; font-weight: 700; flex-shrink: 0;
    }
    .nav-user-name  { font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.2; text-align: left; }
    .nav-user-role  { font-size: 11px; color: #94a3b8; line-height: 1.2; text-align: left; }
    .nav-dropdown {
        display: none; position: absolute; right: 0; top: calc(100% + 10px); width: 200px; background: #fff;
        border: 1px solid #eef1f6; border-radius: 14px; overflow: hidden; box-shadow: 0 16px 40px rgba(15,23,42,0.12); z-index: 100;
    }
    .nav-dropdown.open { display: block; }
    .nav-dropdown a, .nav-dropdown button {
        display: flex; align-items: center; gap: 10px; padding: 11px 16px; font-size: 13px; font-weight: 500;
        color: #475569; text-decoration: none; background: none; border: none; width: 100%; text-align: left; cursor: pointer; transition: background 0.15s;
    }
    .nav-dropdown a:hover, .nav-dropdown button:hover { background: #f8fafc; color: #0f172a; }
    .nav-dropdown .dd-danger { color: #dc2626; }
    .nav-dropdown .dd-danger:hover { background: #fef2f2; color: #b91c1c; }
    .nav-dropdown hr { border-color: #eef1f6; margin: 4px 0; }
    .nav-bell {
        position: relative; display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 50%; background: #f8fafc;
        border: 1px solid #eef1f6; cursor: pointer; text-decoration: none; transition: all 0.18s;
    }
    .nav-bell:hover { background: #eff6ff; border-color: #dbeafe; }
    .nav-bell-badge {
        position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px;
        background: #f59e0b; color: #fff; font-size: 10px; font-weight: 700;
        border-radius: 9px; display: flex; align-items: center; justify-content: center;
        padding: 0 4px; border: 2px solid #fff;
    }
    #navNoAccessToast {
        position: fixed; bottom: 24px; right: 24px; z-index: 999;
        background: #1e3a8a; color: #fff; padding: 12px 20px; border-radius: 10px;
        font-size: 13px; font-weight: 500; opacity: 0; transition: opacity 0.3s;
        pointer-events: none; max-width: 300px; display: flex; align-items: center; gap: 8px;
    }
    #navNoAccessToast.show { opacity: 1; }
</style>

<div id="navNoAccessToast"><i class="fas fa-lock"></i> <span>You don't have access to this section.</span></div>

<script>
    function toggleDropdown() {
        document.getElementById('dropdown').classList.toggle('open');
    }
    function toggleMobileSidebar() {
        var sb = document.querySelector('.sidebar');
        if (sb) sb.classList.toggle('mobile-open');
    }
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('dropdown');
        if (dd && !e.target.closest('.nav-user-pill') && !e.target.closest('.nav-dropdown')) {
            dd.classList.remove('open');
        }
    });

    let navNoAccessToastTimer = null;
    function navNoAccessToast() {
        const el = document.getElementById('navNoAccessToast');
        el.classList.add('show');
        clearTimeout(navNoAccessToastTimer);
        navNoAccessToastTimer = setTimeout(function() { el.classList.remove('show'); }, 2500);
    }
</script>