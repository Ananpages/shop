<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed admin user
        if (!User::where('email', 'admin@beibe.com')->exists()) {
            User::create([
                'id'       => Str::uuid(),
                'name'     => 'Admin Beibe',
                'phone'    => '0700000000',
                'email'    => 'admin@beibe.com',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]);
            $this->command->info('✅ Admin created: admin@beibe.com / admin123');
        }

        // Seed categories
        if (Category::count() === 0) {
            foreach (config('beibe.categories') as $cat) {
                Category::create([
                    'id'         => Str::uuid(),
                    'name'       => $cat['name'],
                    'slug'       => $cat['slug'],
                    'icon'       => $cat['icon'],
                    'sort_order' => $cat['sort_order'],
                    'is_active'  => true,
                ]);
            }
            $this->command->info('✅ Categories seeded');
        }

        // Seed demo seller
        if (!User::where('phone', '0712345678')->exists()) {
            $seller = User::create([
                'id'       => Str::uuid(),
                'name'     => 'Demo Seller',
                'phone'    => '0712345678',
                'email'    => 'seller@beibe.com',
                'password' => Hash::make('seller123'),
                'role'     => 'seller',
            ]);

            $shop = \App\Models\Shop::create([
                'id'          => Str::uuid(),
                'user_id'     => $seller->id,
                'name'        => 'Kampala Tech Store',
                'slug'        => 'kampala-tech-store',
                'description' => 'Your #1 tech shop in Kampala',
                'phone'       => '0712345678',
                'district'    => 'Kampala',
                'status'      => 'approved',
            ]);

            // Seed sample products
            $category = Category::where('slug', 'phones')->first();
            if ($category) {
                for ($i = 1; $i <= 6; $i++) {
                    \App\Models\Product::create([
                        'id'             => Str::uuid(),
                        'shop_id'        => $shop->id,
                        'seller_id'      => $seller->id,
                        'category_id'    => $category->id,
                        'name'           => "Samsung Galaxy A{$i}4 (Demo)",
                        'slug'           => "samsung-galaxy-a{$i}4-demo-" . Str::random(6),
                        'description'    => 'A great smartphone with excellent camera and battery life.',
                        'original_price' => 800000 + ($i * 50000),
                        'discount_price' => 750000 + ($i * 40000),
                        'stock'          => 10 + $i,
                        'district'       => 'Kampala',
                        'images'         => json_encode([]),
                        'specifications' => json_encode([
                            ['key' => 'RAM',     'value' => '6GB'],
                            ['key' => 'Storage', 'value' => '128GB'],
                            ['key' => 'Battery', 'value' => '5000mAh'],
                        ]),
                        'tags' => json_encode(['smartphone', 'samsung', 'android']),
                    ]);
                }
            }

            $this->command->info('✅ Demo seller + shop + 6 products created');
            $this->command->info('   Seller login: 0712345678 / seller123');
        }
    }
}
