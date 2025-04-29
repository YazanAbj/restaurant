<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemSeeder extends Seeder
{
    public function run()
    {
        DB::table('menu_items')->insert([
            [
                'name' => 'Falafel',
                'description' => 'Crispy and delicious fried chickpea balls',
                'price' => 5.00,
                'category' => 'arabic',
                'image_path' => 'falafel.jpg',
                'availability_status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hummus',
                'description' => 'Smooth chickpea dip with olive oil and spices',
                'price' => 4.50,
                'category' => 'arabic',
                'image_path' => 'hummus.jpg',
                'availability_status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Burger',
                'description' => 'Juicy beef patty with lettuce, tomato, and cheese',
                'price' => 8.50,
                'category' => 'snacks',
                'image_path' => 'burger.jpg',
                'availability_status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chocolate Cake',
                'description' => 'Rich and moist chocolate cake with a creamy filling',
                'price' => 6.00,
                'category' => 'desserts',
                'image_path' => 'chocolate_cake.jpg',
                'availability_status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
