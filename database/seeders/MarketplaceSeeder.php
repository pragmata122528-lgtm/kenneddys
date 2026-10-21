<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@marketplace.pk'],
            [
                'username' => 'marketplace_admin',
                'password' => Hash::make('Admin1234!'),
                'role' => UserRole::Admin,
            ]
        );

        $customer = User::firstOrCreate(
            ['email' => 'customer@gmail.com'],
            [
                'username' => 'usman_customer',
                'password' => Hash::make('Customer1234!'),
                'role' => UserRole::Customer,
            ]
        );

        $vendorData = [
            [
                'username' => 'khaadi_official',
                'email' => 'vendor.khaadi@pakshop.pk',
                'shop_name' => 'Khaadi Traditional Wear',
                'shop_description' => 'Authentic hand-stitched Pakistani apparel, shawls, and traditional footwear.',
                'products' => [
                    [
                        'name' => 'Royal Embroidered Kurta & Shalwar',
                        'description' => 'Fine cotton stitched kurta featuring delicate thread embroidery on collar and cuffs.',
                        'image' => 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=800&q=80',
                        'price' => 6500.00,
                        'stock_quantity' => 35,
                        'percentage_discount' => 15,
                    ],
                    [
                        'name' => 'Pure Pashmina Wool Shawl',
                        'description' => 'Exquisite winter pashmina shawl woven from premium soft wool with traditional borders.',
                        'image' => 'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?auto=format&fit=crop&w=800&q=80',
                        'price' => 12000.00,
                        'stock_quantity' => 20,
                        'percentage_discount' => 10,
                    ],
                    [
                        'name' => 'Handmade Peshawari Chappal',
                        'description' => 'Traditional genuine leather Peshawari chappal crafted with durable tire-sole finish.',
                        'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=800&q=80',
                        'price' => 4200.00,
                        'stock_quantity' => 40,
                        'percentage_discount' => null,
                    ],
                ],
            ],
            [
                'username' => 'technest_pk',
                'email' => 'vendor.technest@pakshop.pk',
                'shop_name' => 'TechNest Pakistan',
                'shop_description' => 'High-performance audio gear, smart wearables, and computing accessories.',
                'products' => [
                    [
                        'name' => 'Wireless Active Noise-Canceling Headphones',
                        'description' => 'Over-ear Bluetooth 5.3 headphones with 40-hour battery life and deep bass tuning.',
                        'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',
                        'price' => 8500.00,
                        'stock_quantity' => 50,
                        'percentage_discount' => 20,
                    ],
                    [
                        'name' => 'Ultra AMOLED Smart Fitness Watch',
                        'description' => 'Waterproof smartwatch with continuous heart-rate monitoring, SPO2, and sleep tracking.',
                        'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',
                        'price' => 5800.00,
                        'stock_quantity' => 65,
                        'percentage_discount' => 10,
                    ],
                    [
                        'name' => 'Mechanical RGB Gaming Keyboard',
                        'description' => 'Tenkeyless tactile brown switch keyboard with customizable per-key RGB backlighting.',
                        'image' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=800&q=80',
                        'price' => 7200.00,
                        'stock_quantity' => 30,
                        'percentage_discount' => null,
                    ],
                ],
            ],
            [
                'username' => 'urban_leather',
                'email' => 'vendor.leather@pakshop.pk',
                'shop_name' => 'Urban Leather Crafts',
                'shop_description' => 'Handmade full-grain leather bags, minimalist wallets, and daily travel essentials.',
                'products' => [
                    [
                        'name' => 'Vintage Full-Grain Leather Backpack',
                        'description' => 'Durable vintage travel and laptop backpack crafted from 100% genuine vegetable-tanned leather.',
                        'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
                        'price' => 9500.00,
                        'stock_quantity' => 25,
                        'percentage_discount' => 5,
                    ],
                    [
                        'name' => 'Handcrafted Bifold Leather Wallet',
                        'description' => 'Ultra-slim genuine leather wallet equipped with RFID blocking and 8 card slots.',
                        'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?auto=format&fit=crop&w=800&q=80',
                        'price' => 2800.00,
                        'stock_quantity' => 80,
                        'percentage_discount' => null,
                    ],
                ],
            ],
            [
                'username' => 'artisandecor_pk',
                'email' => 'vendor.artisan@pakshop.pk',
                'shop_name' => 'Artisan Home & Living',
                'shop_description' => 'Handcrafted ceramic pottery, organic scented candles, and cozy desk decor.',
                'products' => [
                    [
                        'name' => 'Handmade Ceramic Coffee Mug Set',
                        'description' => 'Set of two artisanal kiln-fired stoneware mugs with smooth glaze interior.',
                        'image' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=800&q=80',
                        'price' => 2400.00,
                        'stock_quantity' => 45,
                        'percentage_discount' => null,
                    ],
                    [
                        'name' => 'Scented Soy Wax Aromatherapy Candle',
                        'description' => 'Eco-friendly natural soy candle infused with calming lavender and vanilla essential oils.',
                        'image' => 'https://images.unsplash.com/photo-1603006905003-be475563bc59?auto=format&fit=crop&w=800&q=80',
                        'price' => 1800.00,
                        'stock_quantity' => 60,
                        'percentage_discount' => 15,
                    ],
                    [
                        'name' => 'Minimalist Wooden Desk Lamp',
                        'description' => 'Nordic solid pine adjustable desk lamp with warm LED bulb included.',
                        'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',
                        'price' => 4900.00,
                        'stock_quantity' => 22,
                        'percentage_discount' => null,
                    ],
                ],
            ],
            [
                'username' => 'gourmet_naturals',
                'email' => 'vendor.gourmet@pakshop.pk',
                'shop_name' => 'Gourmet Naturals Pakistan',
                'shop_description' => '100% pure wild-harvested honey, mountain spices, and herbal tea blends.',
                'products' => [
                    [
                        'name' => 'Pure Himalayan Sidr Raw Honey (500g)',
                        'description' => 'Unpasteurized and unfiltered wild bee Sidr honey harvested from the Karakoram valleys.',
                        'image' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?auto=format&fit=crop&w=800&q=80',
                        'price' => 3200.00,
                        'stock_quantity' => 70,
                        'percentage_discount' => 12,
                    ],
                    [
                        'name' => 'Organic Kashmiri Kahwa Green Tea (200g)',
                        'description' => 'Whole green tea leaves blended with crushed cardamom, cinnamon, and Kashmiri saffron.',
                        'image' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=800&q=80',
                        'price' => 1450.00,
                        'stock_quantity' => 90,
                        'percentage_discount' => null,
                    ],
                ],
            ],
        ];

        foreach ($vendorData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'username' => $data['username'],
                    'password' => Hash::make('Vendor1234!'),
                    'role' => UserRole::Vendor,
                ]
            );

            $vendor = Vendor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'shop_name' => $data['shop_name'],
                    'shop_description' => $data['shop_description'],
                    'is_approved' => true,
                ]
            );

            $vendor->update(['is_approved' => true]);

            foreach ($data['products'] as $index => $prod) {
                Product::updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'name' => $prod['name'],
                    ],
                    [
                        'description' => $prod['description'],
                        'image' => $prod['image'],
                        'price' => $prod['price'],
                        'stock_quantity' => $prod['stock_quantity'],
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'percentage_discount' => $prod['percentage_discount'],
                    ]
                );
            }
        }
    }
}
