<?php

namespace Database\Seeders;

use App\Models\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'device', 'name_ar' => 'فلتر مياه'],
            ['name' => 'accessories', 'name_ar' => 'قطع غيار'],
            ['name' => 'stages', 'name_ar' => 'مراحل'],
        ];

        foreach ($types as $type) {
            ProductType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
