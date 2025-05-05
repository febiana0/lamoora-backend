<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Fashion'],
            ['name' => 'Elektronik'],
            ['name' => 'Makanan'],
        ]);
    }
}
