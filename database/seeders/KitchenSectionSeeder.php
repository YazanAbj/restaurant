<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KitchenSection;

class KitchenSectionSeeder extends Seeder
{
    public function run()
    {
        KitchenSection::create([
            'name' => 'Drinks Section',
            'categories' => ['drinks'],
        ]);

        KitchenSection::create([
            'name' => 'Snacks Section',
            'categories' => ['snacks'],
        ]);

        KitchenSection::create([
            'name' => 'Arabic Section',
            'categories' => ['arabic'],
        ]);

        KitchenSection::create([
            'name' => 'Desserts Section',
            'categories' => ['desserts'],
        ]);
    }
}
