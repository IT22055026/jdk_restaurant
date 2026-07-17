<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    /**
     * Maps a route name's leading segment (after stripping a literal "api."
     * prefix) to the module name that must be on the user's role to proceed.
     * Route names with no mapped prefix (dashboard, login, logout, etc.) are
     * left ungated — this only enforces access for routes tied to an actual
     * module in the `modules` table, mirroring what the sidebar already shows.
     */
    private const ROUTE_MODULE_MAP = [
        'pos' => 'POS & Billing',
        'order' => 'POS & Billing',
        'token' => 'POS & Billing',
        'sales-report' => 'Sales Report',
        'offers' => 'Discounts & Offers',
        'customers' => 'Customer Management',
        'categories' => 'Category Management',
        'products' => 'Inventory & Products',
        'inventory' => 'Inventory & Products',
        'stock' => 'Inventory & Products',
        'ingredients' => 'Included Items',
        'suppliers' => 'Supplier Management',
        'wastages' => 'Wastage Management',
        'wastage' => 'Wastage Management',
        'employees' => 'Employee Management',
        'shifts' => 'Shifts & Till Management',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'qr' => 'Settings',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return $next($request);
        }

        $segments = explode('.', $routeName);
        if ($segments[0] === 'api') {
            array_shift($segments);
        }

        $requiredModule = self::ROUTE_MODULE_MAP[$segments[0] ?? ''] ?? null;

        if (!$requiredModule) {
            return $next($request);
        }

        $user = $request->user();
        $hasAccess = $user?->role?->modules()->where('name', $requiredModule)->exists() ?? false;

        if (!$hasAccess) {
            abort(403, "You don't have access to this section.");
        }

        return $next($request);
    }
}
