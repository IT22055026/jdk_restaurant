<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Product;
use App\Models\Ingredient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.navbar', function ($view) {
            $lowStockCount = 0;
            if (auth()->check()) {
                $productLow = Product::where('is_unlimited_stock', false)
                    ->whereNotNull('low_stock_threshold')
                    ->whereRaw('quantity <= low_stock_threshold')
                    ->count();

                $ingredientLow = Ingredient::whereNotNull('low_stock_threshold')
                    ->whereRaw('quantity <= low_stock_threshold')
                    ->count();

                $lowStockCount = $productLow + $ingredientLow;
            }
            $view->with('lowStockCount', $lowStockCount);
        });
    }
}
