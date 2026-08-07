<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Superseded by the Purchases module (raw materials are now bought there).
        // Keep the row rather than delete it — older expenses may still reference it.
        ExpenseCategory::where('name', 'Groceries & Raw Materials')->update(['is_active' => false]);

        // Rename in place so any expenses already recorded against this category
        // keep pointing at the same row.
        $staff = ExpenseCategory::where('name', 'Staff Salaries & Wages')->first();
        if ($staff) {
            $staff->update([
                'name' => 'Staff',
                'description' => 'Staff advances, wages, food, accommodation, and loans',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        } else {
            $staff = ExpenseCategory::create([
                'name' => 'Staff',
                'description' => 'Staff advances, wages, food, accommodation, and loans',
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        $staffSubcategories = [
            'Advance' => 'Cash advances given to staff',
            'Wages' => 'Daily / weekly staff wages',
            'Food' => 'Staff meals provided during shifts',
            'Accommodation' => 'Staff lodging costs',
            'Loan' => 'Loans given to staff',
        ];

        foreach (array_values($staffSubcategories) as $i => $description) {
            $name = array_keys($staffSubcategories)[$i];
            ExpenseCategory::updateOrCreate(
                ['name' => $name, 'parent_id' => $staff->id],
                ['description' => $description, 'is_active' => true, 'sort_order' => $i + 1]
            );
        }

        $flatCategories = [
            [
                'name' => 'Utilities',
                'description' => 'Electricity, water, gas, and internet bills',
                'sort_order' => 2,
            ],
            [
                'name' => 'Rent & Property',
                'description' => 'Restaurant rent and related charges',
                'sort_order' => 3,
            ],
            [
                'name' => 'Marketing & Promotion',
                'description' => 'Advertising, flyers, social media promotions',
                'sort_order' => 4,
            ],
            [
                'name' => 'Maintenance & Repairs',
                'description' => 'Equipment maintenance, repairs, cleaning services',
                'sort_order' => 5,
            ],
            [
                'name' => 'Licenses, Permits & Fees',
                'description' => 'Business permits, licenses, and regulatory fees',
                'sort_order' => 6,
            ],
            [
                'name' => 'Office Supplies',
                'description' => 'Stationery, printing, and general office needs',
                'sort_order' => 7,
            ],
            [
                'name' => 'Packaging & Disposables',
                'description' => 'Takeaway containers, bags, napkins, disposable cutlery',
                'sort_order' => 8,
            ],
            [
                'name' => 'Transportation & Delivery',
                'description' => 'Fuel, vehicle maintenance, delivery charges',
                'sort_order' => 9,
            ],
            [
                'name' => 'Miscellaneous',
                'description' => 'Other unforeseen expenses',
                'sort_order' => 10,
            ],
        ];

        foreach ($flatCategories as $category) {
            ExpenseCategory::updateOrCreate(
                ['name' => $category['name'], 'parent_id' => null],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ]
            );
        }
    }
}
