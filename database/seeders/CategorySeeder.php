<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Starters', 'color' => '#ef4444', 'icon' => 'leaf', 'sort_order' => 1],
            ['name' => 'Mains', 'color' => '#f97316', 'icon' => 'utensils', 'sort_order' => 2],
            ['name' => 'Desserts', 'color' => '#ec4899', 'icon' => 'cake-candles', 'sort_order' => 3],
            ['name' => 'Drinks', 'color' => '#06b6d4', 'icon' => 'cup-straw', 'sort_order' => 4],
            ['name' => 'Bar Items', 'color' => '#8b5cf6', 'icon' => 'wine-glass', 'sort_order' => 5],
            ['name' => 'Specials', 'color' => '#10b981', 'icon' => 'star', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            Category::create(array_merge($category, ['status' => 'active']));
        }
    }
}
