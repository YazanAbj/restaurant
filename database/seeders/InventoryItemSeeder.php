<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryItemSeeder extends Seeder
{
    public function run()
    {
        DB::table('inventory_items')->insert([
            [
                'name' => 'Tomato',
                'description' => 'Fresh red tomatoes',
                'category' => 'Vegetable',
                'quantity' => 100,
                'unit' => 'kg',
                'price_per_unit' => 2.50,
                'supplier_name' => 'Fresh Farms',
                'received_date' => Carbon::now()->format('Y-m-d'),
                'expiry_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'low_stock' => false,
                'photo' => 'tomato.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chicken Breast',
                'description' => 'Boneless skinless chicken breasts',
                'category' => 'Meat',
                'quantity' => 50,
                'unit' => 'kg',
                'price_per_unit' => 8.00,
                'supplier_name' => 'Meat Suppliers Inc.',
                'received_date' => Carbon::now()->format('Y-m-d'),
                'expiry_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
                'low_stock' => false,
                'photo' => 'chicken_breast.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Olive Oil',
                'description' => 'Extra virgin olive oil',
                'category' => 'Condiment',
                'quantity' => 30,
                'unit' => 'liters',
                'price_per_unit' => 12.00,
                'supplier_name' => 'Olive Farms',
                'received_date' => Carbon::now()->format('Y-m-d'),
                'expiry_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'low_stock' => false,
                'photo' => 'olive_oil.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Rice',
                'description' => 'Long-grain basmati rice',
                'category' => 'Grain',
                'quantity' => 200,
                'unit' => 'kg',
                'price_per_unit' => 3.00,
                'supplier_name' => 'Grain Suppliers Ltd.',
                'received_date' => Carbon::now()->format('Y-m-d'),
                'expiry_date' => Carbon::now()->addYear()->format('Y-m-d'),
                'low_stock' => false,
                'photo' => 'rice.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
