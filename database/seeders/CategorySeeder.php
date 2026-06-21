<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $deviceId      = ProductType::where('name', 'device')->value('id');
        $accessoriesId = ProductType::where('name', 'accessories')->value('id');
        $stagesId      = ProductType::where('name', 'stages')->value('id');

        if (! $deviceId || ! $accessoriesId || ! $stagesId) {
            return;
        }

        $roots = [
            ['name' => 'filteration devices', 'product_type_id' => $deviceId, 'number_of_stages' => 0],
            ['name' => 'RO devices', 'product_type_id' => $deviceId, 'number_of_stages' => 0],
            ['name' => 'compact devices', 'product_type_id' => $deviceId, 'number_of_stages' => 0],
            ['name' => 'stages', 'product_type_id' => $stagesId, 'number_of_stages' => 0],
            ['name' => 'cartridge', 'product_type_id' => $stagesId, 'number_of_stages' => 0],
            ['name' => 'compact stages', 'product_type_id' => $stagesId, 'number_of_stages' => 0],
            ['name' => 'اصلاح', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'تثبيت', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'تحكم', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'تكميلي', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'ضغط', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'كهربي', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'وصلات', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
            ['name' => 'وعاء', 'product_type_id' => $accessoriesId, 'number_of_stages' => null],
        ];

        foreach ($roots as $root) {
            Category::updateOrCreate(
                ['name' => $root['name'], 'parent_category_id' => null],
                $root
            );
        }

        $stagesRoot = Category::where('name', 'stages')->whereNull('parent_category_id')->first();

        if ($stagesRoot) {
            $stageChildren = [
                ['name' => 'stage one', 'number_of_stages' => 1],
                ['name' => 'stage two', 'number_of_stages' => 2],
                ['name' => 'stage three', 'number_of_stages' => 3],
                ['name' => 'stage four', 'number_of_stages' => 4],
                ['name' => 'stage five', 'number_of_stages' => 5],
                ['name' => 'stage six', 'number_of_stages' => 6],
                ['name' => 'stage seven', 'number_of_stages' => 7],
                ['name' => 'stage eight', 'number_of_stages' => 8],
            ];

            foreach ($stageChildren as $child) {
                Category::updateOrCreate(
                    [
                        'name'               => $child['name'],
                        'parent_category_id' => $stagesRoot->id,
                    ],
                    [
                        'product_type_id'  => $stagesId,
                        'number_of_stages' => $child['number_of_stages'],
                    ]
                );
            }
        }
    }
}
