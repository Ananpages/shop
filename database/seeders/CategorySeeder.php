<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // ===== MOST POPULAR FIRST =====
            ['name' => 'Phones & Tablets',       'icon' => 'Smartphone',     'sort_order' => 1],
            ['name' => 'Fashion & Clothing',      'icon' => 'Shirt',          'sort_order' => 2],
            ['name' => 'Electronics',             'icon' => 'Zap',            'sort_order' => 3],
            ['name' => 'Food & Groceries',        'icon' => 'ShoppingBasket', 'sort_order' => 4],
            ['name' => 'Home & Living',           'icon' => 'Home',           'sort_order' => 5],
            ['name' => 'Beauty & Personal Care',  'icon' => 'Sparkles',       'sort_order' => 6],
            ['name' => 'Shoes & Footwear',        'icon' => 'Footprints',     'sort_order' => 7],
            ['name' => 'Baby & Kids',             'icon' => 'Baby',           'sort_order' => 8],
            ['name' => 'Health & Wellness',       'icon' => 'HeartPulse',     'sort_order' => 9],
            ['name' => 'Computers & Laptops',     'icon' => 'Laptop',         'sort_order' => 10],

            // ===== SECONDARY =====
            ['name' => 'TVs & Audio',             'icon' => 'Tv',             'sort_order' => 11],
            ['name' => 'Cameras & Photography',   'icon' => 'Camera',         'sort_order' => 12],
            ['name' => 'Gaming',                  'icon' => 'Gamepad2',       'sort_order' => 13],
            ['name' => 'Bags & Luggage',          'icon' => 'BriefcaseBusiness', 'sort_order' => 14],
            ['name' => 'Watches & Accessories',   'icon' => 'Watch',          'sort_order' => 15],
            ['name' => 'Jewelry',                 'icon' => 'Gem',            'sort_order' => 16],
            ['name' => 'Sports & Fitness',        'icon' => 'Dumbbell',       'sort_order' => 17],
            ['name' => 'Outdoor & Camping',       'icon' => 'Tent',           'sort_order' => 18],
            ['name' => 'Automotive',              'icon' => 'Car',            'sort_order' => 19],
            ['name' => 'Motorbikes & Bicycles',   'icon' => 'Bike',           'sort_order' => 20],
            ['name' => 'Furniture',               'icon' => 'Sofa',           'sort_order' => 21],
            ['name' => 'Kitchen & Appliances',    'icon' => 'ChefHat',        'sort_order' => 22],
            ['name' => 'Garden & Outdoor',        'icon' => 'Leaf',           'sort_order' => 23],
            ['name' => 'Tools & Hardware',        'icon' => 'Wrench',         'sort_order' => 24],
            ['name' => 'Building & Construction', 'icon' => 'HardHat',        'sort_order' => 25],
            ['name' => 'Books & Stationery',      'icon' => 'BookOpen',       'sort_order' => 26],
            ['name' => 'Music & Instruments',     'icon' => 'Music',          'sort_order' => 27],
            ['name' => 'Toys & Games',            'icon' => 'Blocks',         'sort_order' => 28],
            ['name' => 'Art & Crafts',            'icon' => 'Palette',        'sort_order' => 29],
            ['name' => 'Pet Supplies',            'icon' => 'PawPrint',       'sort_order' => 30],
            ['name' => 'Agriculture & Farming',   'icon' => 'Tractor',        'sort_order' => 31],
            ['name' => 'Office Supplies',         'icon' => 'Printer',        'sort_order' => 32],
            ['name' => 'Solar & Electrical',      'icon' => 'Sun',            'sort_order' => 33],
            ['name' => 'Services',                'icon' => 'ConciergeBell',  'sort_order' => 34],
            ['name' => 'Real Estate',             'icon' => 'Building2',      'sort_order' => 35],
            ['name' => 'Jobs & Education',        'icon' => 'GraduationCap',  'sort_order' => 36],
            ['name' => 'Events & Tickets',        'icon' => 'Ticket',         'sort_order' => 37],
            ['name' => 'Other',                   'icon' => 'Package',        'sort_order' => 99],
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        foreach ($categories as $cat) {
            DB::table('categories')->insert([
                'id'         => Str::uuid(),
                'name'       => $cat['name'],
                'slug'       => Str::slug($cat['name']),
                'icon'       => $cat['icon'],
                'sort_order' => $cat['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ ' . count($categories) . ' categories seeded!');
    }
}
