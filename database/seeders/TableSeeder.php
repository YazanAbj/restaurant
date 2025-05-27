<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class TableSeeder extends Seeder
{
    public function run()
    {
        $tables = [
            ['capacity' => 2],
            ['capacity' => 4],
            ['capacity' => 6],
            ['capacity' => 8],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
