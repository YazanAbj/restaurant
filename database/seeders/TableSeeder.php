<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class TableSeeder extends Seeder
{
    public function run()
    {
        $tables = [
            ['table_number' => '1', 'capacity' => 2],
            ['table_number' => '2', 'capacity' => 4],
            ['table_number' => '3', 'capacity' => 6],
            ['table_number' => '4', 'capacity' => 8],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
