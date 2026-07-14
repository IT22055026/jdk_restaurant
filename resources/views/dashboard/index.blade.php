<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — Restaurant BYOB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f1f5f9 0%, #e9eef5 100%); }

        .stat-card {
            background: #fff; border-radius: 16px;
            padding: 22px 24px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: all 0.22s ease;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }

        /* ── Module cards ── */
        .module-card {
            border-radius: 16px; border: 1.5px solid transparent;
            transition: all 0.28s ease; position: relative; overflow: hidden;
            text-decoration: none; display: block;
        }
        .module-card::after {
            content: ''; position: absolute; inset: 0;
            background: rgba(0,0,0,0); transition: background 0.28s;
            border-radius: 16px;
        }
        .module-card:hover::after  { background: rgba(0,0,0,0.04); }
        .module-card:hover {
            box-shadow: 0 14px 36px rgba(0,0,0,0.13);
            transform: translateY(-5px);
            border-color: rgba(0,0,0,0.1);
        }
        .module-card:nth-child(1)  { background: #fcc2c2; }
        .module-card:nth-child(2)  { background: #c6e5fc; }
        .module-card:nth-child(3)  { background: #c6f6d5; }
        .module-card:nth-child(4)  { background: #fef08a; }
        .module-card:nth-child(5)  { background: #e9d5ff; }
        .module-card:nth-child(6)  { background: #99f6e4; }
        .module-card:nth-child(7)  { background: #fed7aa; }
        .module-card:nth-child(8)  { background: #fbcfe8; }
        .module-card:nth-child(9)  { background: #c7d2fe; }
        .module-card:nth-child(10) { background: #d9f99d; }
        .module-card:nth-child(11) { background: #fca5a5; }
        .module-card:nth-child(12) { background: #bfdbfe; }

        .module-icon {
            width: 50px; height: 50px;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(6px);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #374151;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .badge-role {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #fff; border: 1px solid rgba(255,255,255,0.1);
        }

        /* Dark mode overrides specific to this page */
        html.dark-mode body { background: #0a0e17; }
        html.dark-mode .stat-card { background: #10162a; border-color: #1f2942; }
        html.dark-mode .module-icon { background: rgba(255,255,255,0.08); color: #e8ecf4; }
        html.dark-mode .module-card:nth-child(1)  { background: #241414; }
        html.dark-mode .module-card:nth-child(2)  { background: #101f3d; }
        html.dark-mode .module-card:nth-child(3)  { background: #10261c; }
        html.dark-mode .module-card:nth-child(4)  { background: #2a2410; }
        html.dark-mode .module-card:nth-child(5)  { background: #201035; }
        html.dark-mode .module-card:nth-child(6)  { background: #0f2a26; }
        html.dark-mode .module-card:nth-child(7)  { background: #2a1c10; }
        html.dark-mode .module-card:nth-child(8)  { background: #2a1020; }
        html.dark-mode .module-card:nth-child(9)  { background: #181c3d; }
        html.dark-mode .module-card:nth-child(10) { background: #1c2410; }
        html.dark-mode .module-card:nth-child(11) { background: #2a1414; }
        html.dark-mode .module-card:nth-child(12) { background: #101f3d; }
        html.dark-mode .module-card h3,
        html.dark-mode .module-card p { color: #e8ecf4 !important; }
        html.dark-mode a.module-card[style*="background"] { background: #10162a !important; }
    </style>
    @include('layouts.dark-mode')
</head>
<body>

    <!-- Navbar -->
    @include('layouts.navbar')

    <!-- Page content -->
    <div style="padding-top: 67px;">
        <div class="w-full px-6 py-8 max-w-screen-2xl mx-auto">

            <!-- Page header -->
            <div class="mb-8">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900" style="letter-spacing:-0.5px;">Dashboard</h1>
                        <p class="text-gray-500 mt-1 text-sm">Welcome back, <strong class="text-gray-700">{{ $user->name }}</strong>!</p>
                    </div>
                    <span class="badge-role px-4 py-2 rounded-full text-sm font-semibold">
                        <i class="fas fa-shield-halved mr-1"></i>{{ $role->name }} Access
                    </span>
                </div>
            </div>

            <!-- Stats — 6 cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                <!-- Total Sales -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">Total Sales</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">LKR {{ number_format($totalSales, 2) }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#fde8e8,#fecaca);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-rupee-sign" style="color:#dc2626;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
                <!-- Active Orders -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">Active Orders</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $activeOrders }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#e8f4fd,#bfdbfe);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-shopping-cart" style="color:#2563eb;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
                <!-- Inventory Items -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">Inventory Items</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $inventoryItems }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#edfcf2,#bbf7d0);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-boxes" style="color:#16a34a;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
                <!-- Active Users -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">Active Users</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">{{ $activeUsers }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#f3e8fd,#e9d5ff);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-users" style="color:#7c3aed;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
                <!-- Today's Sales -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">Today's Sales</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">LKR {{ number_format($todaySales, 2) }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#fef9e8,#fde68a);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-sun" style="color:#d97706;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
                <!-- This Month -->
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-xs font-semibold uppercase tracking-wide">This Month</p>
                            <p class="text-lg font-bold text-gray-900 mt-1">LKR {{ number_format($monthRevenue, 2) }}</p>
                        </div>
                        <div style="width:40px;height:40px;background:linear-gradient(135deg,#e8fdf9,#99f6e4);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-calendar-check" style="color:#0d9488;font-size:14px;"></i>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Modules -->
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Available Modules</h2>
                    <span class="text-xs text-gray-400 font-medium">{{ $modules->count() }} modules</span>
                </div>

                @if($modules->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach($modules as $module)
                            <a href="{{ route($module->route) }}" class="module-card p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="module-icon">
                                        <i class="fas fa-{{ $module->icon }}"></i>
                                    </div>
                                    <i class="fas fa-arrow-right" style="color:rgba(0,0,0,0.2); font-size:14px; margin-top:4px;"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-1">{{ $module->name }}</h3>
                                <p class="text-gray-500 text-sm" style="line-height:1.5;">{{ $module->description ?? 'Manage ' . strtolower($module->name) }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white p-10 rounded-2xl text-center border border-gray-100">
                        <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
                        <p class="text-gray-500">No modules available for your role yet.</p>
                    </div>
                @endif
            </div>
            <!-- History Cards -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Quick Access</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Order History Card -->
                    <a href="{{ route('order.history') }}" class="module-card p-6" style="background: #fef3c7;">
                        <div class="flex items-start justify-between mb-4">
                            <div class="module-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <i class="fas fa-arrow-right" style="color:rgba(0,0,0,0.2); font-size:14px; margin-top:4px;"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Order History</h3>
                        <p class="text-gray-500 text-sm" style="line-height:1.5;">View past orders and reprint receipts</p>
                    </a>

                    <!-- Token History Card -->
                    <a href="{{ route('token.history') }}" class="module-card p-6" style="background: #dbeafe;">
                        <div class="flex items-start justify-between mb-4">
                            <div class="module-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <i class="fas fa-arrow-right" style="color:rgba(0,0,0,0.2); font-size:14px; margin-top:4px;"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-1">Token History</h3>
                        <p class="text-gray-500 text-sm" style="line-height:1.5;">Reprint kitchen tokens</p>
                    </a>
                </div>
            </div>

            <!-- Mobile quick access -->
            <div class="lg:hidden mt-8 pb-8">
                <h2 class="text-base font-bold text-gray-900 mb-4">Quick Access</h2>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($modules as $module)
                        <a href="{{ route($module->route) }}" class="bg-white p-3 rounded-xl text-center border border-gray-100 hover:border-red-400 transition-colors">
                            <i class="fas fa-{{ $module->icon }} text-red-500 text-xl mb-1 block"></i>
                            <p class="text-xs font-semibold text-gray-700">{{ substr($module->name, 0, 10) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    
</body>
</html>
