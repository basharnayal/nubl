<?php




namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuItemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Restaurant' => [
                'Appetizers',
                'Salads',
                'Soups',
                'Burgers',
                'Sandwiches',
                'Pizza',
                'Pasta',
                'Rice Dishes',
                'Grills',
                'Seafood',
                'Chicken',
                'Beef',
                'Vegetarian',
                'Kids Meals',
                'Sauces & Sides',
                'Desserts',
                'Hot Drinks',
                'Cold Drinks',
                'Fresh Juices'
            ],

            'Catering' => [
                'Platters',
                'Party Trays',
                'Sandwich Platters',
                'Canapés',
                'Salads (Bulk)',
                'Main Dishes (Bulk)',
                'Rice (Bulk)',
                'BBQ Catering',
                'Seafood Catering',
                'Dessert Trays',
                'Beverage Service',
                'Breakfast Catering',
                'Lunch Boxes'
            ],
            'Bakery' => [
                'Bread',
                'Buns & Rolls',
                'Croissants',
                'Pastries',
                'Cakes',
                'Cupcakes',
                'Cookies',
                'Brownies',
                'Pies & Tarts',
                'Doughnuts',
                'Muffins',
                'Cheesecakes',
                'Arabic Sweets',
                'Savory Pastries',
                'Baking Ingredients'
            ],
            'Grocery' => [
                'Fruits',
                'Vegetables',
                'Fresh Herbs',
                'Meat',
                'Poultry',
                'Seafood',
                'Dairy',
                'Eggs',
                'Frozen Foods',
                'Canned Goods',
                'Pasta & Rice',
                'Flour & Baking',
                'Snacks',
                'Sweets & Chocolate',
                'Beverages',
                'Water',
                'Coffee & Tea',
                'Spices & Seasoning',
                'Sauces & Condiments',
                'Oils & Ghee',
                'Breakfast Items',
                'Baby Products',
                'Cleaning Supplies',
                'Personal Care'
            ],
            'Food truck' => [
                'Street Snacks',
                'Burgers',
                'Shawarma/Wraps',
                'Tacos',
                'Fries',
                'Loaded Fries',
                'Grilled Items',
                'Desserts',
                'Ice Cream',
                'Coffee',
                'Cold Drinks',
                'Smoothies',
                'Specialty Items'
            ],
            'Other' => [
                'Other',
                'Seasonal',
                'Bundles',
                'Promotions' // Changed 'Other (generic)' to 'Other'
            ],
        ];

        foreach ($categories as $businessCategory => $items) {
            foreach ($items as $itemName) {
                \App\Models\MenuItemCategory::firstOrCreate([
                    'business_category' => $businessCategory,
                    'slug' => \Illuminate\Support\Str::slug($itemName),
                ], [
                    'name' => $itemName,
                    'is_active' => true,
                ]);
            }
        }
    }
}
