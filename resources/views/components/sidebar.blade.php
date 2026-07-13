<!-- Sidebar -->
<div class="sidebar w-64 min-h-screen bg-white shadow-lg fixed left-0 top-16 hidden lg:block overflow-y-auto">
    <div class="p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-6">Modules</h2>
        <nav class="space-y-2">
            @foreach($modules as $module)
                <a href="{{ route($module->route) }}" class="nav-item p-3 rounded-lg text-sm font-medium flex items-center space-x-3 transition-colors
                    {{ request()->routeIs($module->route) ? 'active-nav bg-red-50 text-red-900 border-l-4 border-red-600' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="fas fa-{{ $module->icon }}"></i>
                    <span>{{ $module->name }}</span>
                </a>
            @endforeach
        </nav>
    </div>
</div>
