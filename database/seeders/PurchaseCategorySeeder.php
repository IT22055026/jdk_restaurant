<?php

namespace Database\Seeders;

use App\Models\PurchaseCategory;
use Illuminate\Database\Seeder;

class PurchaseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $tree = [
            [
                'name' => 'Vegetables',
                'children' => [
                    'Cabbage', 'Leeks', 'Cucumber', 'Onion', 'Tomato', 'Carrot',
                    'Potato', 'Beans', 'Brinjal', 'Capsicum', 'Pumpkin',
                    'Beetroot', 'Garlic', 'Ginger', 'Lettuce', 'Long Beans',
                ],
            ],
            [
                'name' => 'Meat & Poultry',
                'children' => ['Chicken', 'Beef', 'Mutton', 'Fish', 'Prawns', 'Eggs'],
            ],
            [
                'name' => 'Dairy & Dairy Products',
                'children' => ['Curd', 'Milk', 'Cheese', 'Butter', 'Yogurt'],
            ],
            [
                'name' => 'Oil & Ghee',
                'children' => ['Cooking Oil', 'Ghee', 'Coconut Oil', 'Palm Oil'],
            ],
            [
                'name' => 'Spices & Condiments',
                'children' => [
                    'Chilies', 'Chili Flakes', 'Chili Powder', 'Salt', 'Sugar',
                    'Turmeric Powder', 'Curry Powder', 'Pepper', 'Mustard Seeds',
                    'Cumin Seeds', 'Cinnamon', 'Cloves',
                ],
            ],
            [
                'name' => 'Grains, Rice & Flour',
                'children' => ['Rice', 'Wheat Flour', 'Bread', 'Noodles', 'Pasta'],
            ],
        ];

        foreach ($tree as $mainSort => $main) {
            $mainCategory = PurchaseCategory::updateOrCreate(
                ['name' => $main['name'], 'parent_id' => null],
                ['is_active' => true, 'sort_order' => $mainSort + 1]
            );

            foreach ($main['children'] as $subSort => $childName) {
                PurchaseCategory::updateOrCreate(
                    ['name' => $childName, 'parent_id' => $mainCategory->id],
                    ['is_active' => true, 'sort_order' => $subSort + 1]
                );
            }
        }
    }
}
