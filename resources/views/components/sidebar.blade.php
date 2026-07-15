<!-- Sidebar -->
<div class="sidebar w-64 min-h-screen bg-white fixed left-0 top-16 hidden lg:flex lg:flex-col overflow-y-auto">
    <div class="p-5 flex-1">
        <p class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Menu</p>
        <nav class="space-y-1">
            @foreach($modules as $module)
                @php
                    $routeName = $module->route;
                    $prefix = explode('.', $routeName)[0];
                    $isActive = request()->routeIs($routeName) || request()->routeIs($prefix . '.*');
                    // Special-case: inventory module should be active for product/ingredient/stock routes
                    if ($prefix === 'inventory') {
                        $isActive = $isActive || request()->routeIs('products.*') || request()->routeIs('wastages.*') || request()->routeIs('stock.adjustments.*') || request()->routeIs('ingredients.*');
                    }
                @endphp

                <a href="{{ route($module->route) }}" class="nav-item px-3 py-2.5 rounded-xl text-sm font-medium flex items-center gap-3 transition-colors
                    {{ $isActive ? 'active-nav bg-blue-50 text-blue-600' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800' }}">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 {{ $isActive ? 'bg-white text-blue-600 shadow-sm' : 'bg-gray-50 text-gray-400' }}">
                        <i class="fas fa-{{ $module->icon }} text-sm"></i>
                    </span>
                    <span>{{ $module->route === 'reports.index' ? 'Dashboard' : $module->name }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="p-5 pt-3 mt-auto">
        <div class="sidebar-brand-card rounded-2xl p-4 flex items-center gap-3">
            <img src="{{ asset('images/jaan_logo.jpg') }}" alt="Logo" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
            <div class="min-w-0">
                <p class="text-xs font-bold text-gray-800 truncate">JAAN Network</p>
                <p class="text-[11px] text-gray-400 truncate">POS System</p>
            </div>
        </div>
    </div>
</div>
