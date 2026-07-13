<nav class="navbar fixed top-0 left-0 right-0 z-50 pb-2">
    <div class="page-header-bar"></div>
    <div class="navbar-inner">
        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="navbar-logo-wrap">
            <img src="{{ asset('images/jaan_logo.jpg') }}" alt="Logo" class="navbar-logo">
        </a>

        <!-- Right actions -->
        <div class="navbar-actions">
            <!-- Current page label -->
            <span class="hidden md:block" style="font-size:12px; color:#64748b; font-weight:500; margin-right:6px;">
                {{ request()->routeIs('dashboard') ? 'Dashboard' : (request()->segment(1) ? ucfirst(str_replace('-', ' ', request()->segment(1))) : '') }}
            </span>

            <div class="nav-divider hidden sm:block"></div>

            <!-- QR order notifications -->
            <div style="position:relative;">
                <button type="button" id="qrOrderBell" class="nav-bell" onclick="toggleQrOrderDropdown()" title="Orders placed via QR">
                    <i class="fas fa-receipt" id="qrOrderBellIcon" style="font-size:15px; color:{{ $qrOrderCount > 0 ? '#dc2626' : '#64748b' }};"></i>
                    @if($qrOrderCount > 0)
                        <span class="nav-bell-badge" id="qrOrderBellBadge">{{ $qrOrderCount > 99 ? '99+' : $qrOrderCount }}</span>
                    @else
                        <span class="nav-bell-badge" id="qrOrderBellBadge" style="display:none;"></span>
                    @endif
                </button>

                <div id="qrOrderDropdown" class="nav-dropdown qr-order-dropdown">
                    <div style="padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; justify-content:space-between;">
                        <div style="font-size:13px; font-weight:700; color:#f1f5f9;">
                            <i class="fas fa-qrcode" style="margin-right:6px; color:#dc2626;"></i>QR Orders
                        </div>
                        <button type="button" onclick="markAllQrOrdersSeen()" style="font-size:11px; color:#94a3b8; background:none; border:none; cursor:pointer;">Mark all read</button>
                    </div>
                    <div id="qrOrderList" style="max-height:360px; overflow-y:auto;">
                        <div style="padding:24px 16px; text-align:center; font-size:12px; color:#64748b;">Loading…</div>
                    </div>
                </div>
            </div>

            <!-- Low stock bell -->
            <a href="{{ route('inventory.dashboard') }}" class="nav-bell" title="{{ $lowStockCount > 0 ? $lowStockCount . ' low stock alert(s)' : 'Inventory' }}">
                <i class="fas fa-bell" style="font-size:16px; color:{{ $lowStockCount > 0 ? '#f59e0b' : '#64748b' }};"></i>
                @if($lowStockCount > 0)
                    <span class="nav-bell-badge">{{ $lowStockCount > 99 ? '99+' : $lowStockCount }}</span>
                @endif
            </a>

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
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-gauge-high" style="width:16px;"></i> Dashboard
                    </a>
                    <a href="{{ route('settings.index') }}">
                        <i class="fas fa-gear" style="width:16px;"></i> Settings
                    </a>
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
        background: linear-gradient(90deg, #0f172a 0%, #1e293b 60%, #1a1a2e 100%);
        box-shadow: 0 2px 20px rgba(0,0,0,0.35);
        border-bottom: 1px solid rgba(255,255,255,0.06);
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
    .page-header-bar {
        height: 3px; background: linear-gradient(90deg, #dc2626, #f97316, #dc2626);
        background-size: 200% 100%; animation: shimmer 3s ease infinite;
    }
    @keyframes shimmer {
        0%   { background-position: 0% 0%; }
        50%  { background-position: 100% 0%; }
        100% { background-position: 0% 0%; }
    }
    .navbar-logo-wrap {
        display: flex; align-items: center; text-decoration: none; transition: opacity 0.2s;
    }
    .navbar-logo-wrap:hover { opacity: 0.85; }
    .navbar-logo {
        height: 44px; width: auto; max-width: 160px; object-fit: contain; display: block; border-radius: 5px;
    }
    .navbar-actions { display: flex; align-items: center; gap: 8px; }
    .nav-divider { width: 1px; height: 28px; background: rgba(255,255,255,0.1); margin: 0 4px; }
    .nav-user-pill {
        display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 50px; padding: 5px 14px 5px 6px; cursor: pointer; transition: all 0.18s; outline: none; text-decoration: none; color: inherit;
    }
    .nav-user-pill:hover { background: rgba(255,255,255,0.13); border-color: rgba(255,255,255,0.2); }
    .nav-user-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #dc2626, #991b1b);
        display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; font-weight: 700; flex-shrink: 0;
    }
    .nav-user-name  { font-size: 13px; font-weight: 600; color: #f1f5f9; line-height: 1.2; text-align: left; }
    .nav-user-role  { font-size: 11px; color: #94a3b8; line-height: 1.2; text-align: left; }
    .nav-dropdown {
        display: none; position: absolute; right: 0; top: calc(100% + 10px); width: 200px; background: #1e293b;
        border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; overflow: hidden; box-shadow: 0 16px 40px rgba(0,0,0,0.4); z-index: 100;
    }
    .nav-dropdown.open { display: block; }
    .nav-dropdown a, .nav-dropdown button {
        display: flex; align-items: center; gap: 10px; padding: 11px 16px; font-size: 13px; font-weight: 500;
        color: #cbd5e1; text-decoration: none; background: none; border: none; width: 100%; text-align: left; cursor: pointer; transition: background 0.15s;
    }
    .nav-dropdown a:hover, .nav-dropdown button:hover { background: rgba(255,255,255,0.07); color: #fff; }
    .nav-dropdown .dd-danger { color: #f87171; }
    .nav-dropdown .dd-danger:hover { background: rgba(239,68,68,0.1); color: #ef4444; }
    .nav-dropdown hr { border-color: rgba(255,255,255,0.08); margin: 4px 0; }
    .nav-bell {
        position: relative; display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.1); cursor: pointer; text-decoration: none; transition: all 0.18s;
    }
    .nav-bell:hover { background: rgba(255,255,255,0.13); border-color: rgba(255,255,255,0.2); }
    .nav-bell-badge {
        position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px;
        background: #f59e0b; color: #fff; font-size: 10px; font-weight: 700;
        border-radius: 9px; display: flex; align-items: center; justify-content: center;
        padding: 0 4px; border: 2px solid #0f172a;
    }
    #qrOrderBellBadge { background: #dc2626; }
    #qrOrderBell.has-new { animation: qrPulse 1.4s ease-in-out infinite; }
    @keyframes qrPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(220,38,38,0.45); }
        50%      { box-shadow: 0 0 0 6px rgba(220,38,38,0); }
    }
    .qr-order-dropdown { width: 340px; right: -4px; }
    .qr-order-item {
        display: block; padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06);
        text-decoration: none; cursor: pointer; transition: background 0.15s;
    }
    .qr-order-item:hover { background: rgba(255,255,255,0.06); }
    .qr-order-item.unseen { background: rgba(220,38,38,0.08); }
    .qr-order-item .qoi-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:3px; }
    .qr-order-item .qoi-table { font-size:13px; font-weight:700; color:#f1f5f9; }
    .qr-order-item .qoi-time { font-size:10px; color:#64748b; }
    .qr-order-item .qoi-items { font-size:11px; color:#94a3b8; line-height:1.4; }
    .qr-order-item .qoi-total { font-size:12px; font-weight:700; color:#fb923c; margin-top:3px; }
    .qr-order-item .qoi-dot { width:7px; height:7px; border-radius:50%; background:#dc2626; display:inline-block; margin-right:6px; }
</style>

<script>
    function toggleDropdown() {
        document.getElementById('dropdown').classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        const dd = document.getElementById('dropdown');
        if (dd && !e.target.closest('.nav-user-pill') && !e.target.closest('.nav-dropdown')) {
            dd.classList.remove('open');
        }
    });

    // ── QR order notifications ──
    (function () {
        const POLL_MS = 12000;
        const dropdown = document.getElementById('qrOrderDropdown');
        const bell     = document.getElementById('qrOrderBell');
        const icon     = document.getElementById('qrOrderBellIcon');
        const badge    = document.getElementById('qrOrderBellBadge');
        const listEl   = document.getElementById('qrOrderList');
        if (!dropdown) return;

        let lastUnseenCount = {{ $qrOrderCount ?? 0 }};

        function setBadge(count) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
                icon.style.color = '#dc2626';
                bell.classList.add('has-new');
            } else {
                badge.style.display = 'none';
                icon.style.color = '#64748b';
                bell.classList.remove('has-new');
            }
        }

        function renderOrders(orders) {
            if (!orders.length) {
                listEl.innerHTML = '<div style="padding:24px 16px; text-align:center; font-size:12px; color:#64748b;">No QR orders yet.</div>';
                return;
            }
            listEl.innerHTML = orders.map(function (o) {
                const tableLabel = o.table_name || (o.table_number ? ('Table ' + o.table_number) : 'Table');
                return '<a href="{{ url('/pos') }}?order=' + o.id + '" class="qr-order-item' + (o.seen ? '' : ' unseen') + '" data-order-id="' + o.id + '">'
                    + '<div class="qoi-top">'
                    +   '<span class="qoi-table">' + (o.seen ? '' : '<span class="qoi-dot"></span>') + tableLabel + '</span>'
                    +   '<span class="qoi-time">' + o.created_at_human + '</span>'
                    + '</div>'
                    + '<div class="qoi-items">' + (o.items_summary || (o.items_count + ' item(s)')) + '</div>'
                    + '<div class="qoi-total">LKR ' + o.total.toFixed(2) + (o.customer_name ? ' · ' + o.customer_name : '') + '</div>'
                    + '</a>';
            }).join('');
        }

        async function refresh() {
            try {
                const res  = await fetch('{{ route('pos.qr_orders.notifications') }}');
                if (!res.ok) return;
                const data = await res.json();
                setBadge(data.unseen_count);
                lastUnseenCount = data.unseen_count;
                if (dropdown.classList.contains('open')) {
                    renderOrders(data.orders);
                }
            } catch (e) { /* silent — non-critical */ }
        }

        window.toggleQrOrderDropdown = function () {
            const isOpen = dropdown.classList.toggle('open');
            if (isOpen) refresh();
        };

        window.markAllQrOrdersSeen = async function (e) {
            if (e) e.stopPropagation();
            try {
                await fetch('{{ route('pos.qr_orders.seen_all') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });
                setBadge(0);
                refresh();
            } catch (e) { /* silent */ }
        };

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#qrOrderBell') && !e.target.closest('.qr-order-dropdown')) {
                dropdown.classList.remove('open');
            }
        });

        refresh();
        setInterval(refresh, POLL_MS);
    })();
</script>