<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            ['name_ar' => 'القاهرة',       'name_en' => 'Cairo'],
            ['name_ar' => 'الإسكندرية',    'name_en' => 'Alexandria'],
            ['name_ar' => 'الجيزة',        'name_en' => 'Giza'],
            ['name_ar' => 'القليوبية',     'name_en' => 'Qalyubia'],
            ['name_ar' => 'الشرقية',       'name_en' => 'Sharqia'],
            ['name_ar' => 'الدقهلية',      'name_en' => 'Dakahlia'],
            ['name_ar' => 'البحيرة',       'name_en' => 'Beheira'],
            ['name_ar' => 'كفر الشيخ',    'name_en' => 'Kafr El Sheikh'],
            ['name_ar' => 'الغربية',       'name_en' => 'Gharbia'],
            ['name_ar' => 'المنوفية',      'name_en' => 'Monufia'],
            ['name_ar' => 'الفيوم',        'name_en' => 'Faiyum'],
            ['name_ar' => 'بني سويف',     'name_en' => 'Beni Suef'],
            ['name_ar' => 'المنيا',        'name_en' => 'Minya'],
            ['name_ar' => 'أسيوط',         'name_en' => 'Asyut'],
            ['name_ar' => 'سوهاج',         'name_en' => 'Sohag'],
            ['name_ar' => 'قنا',           'name_en' => 'Qena'],
            ['name_ar' => 'الأقصر',        'name_en' => 'Luxor'],
            ['name_ar' => 'أسوان',         'name_en' => 'Aswan'],
            ['name_ar' => 'البحر الأحمر',  'name_en' => 'Red Sea'],
            ['name_ar' => 'الوادي الجديد', 'name_en' => 'New Valley'],
            ['name_ar' => 'مطروح',         'name_en' => 'Matrouh'],
            ['name_ar' => 'شمال سيناء',   'name_en' => 'North Sinai'],
            ['name_ar' => 'جنوب سيناء',   'name_en' => 'South Sinai'],
            ['name_ar' => 'بورسعيد',       'name_en' => 'Port Said'],
            ['name_ar' => 'الإسماعيلية',   'name_en' => 'Ismailia'],
            ['name_ar' => 'السويس',        'name_en' => 'Suez'],
            ['name_ar' => 'دمياط',         'name_en' => 'Damietta'],
        ];

        DB::table('governorates')->insertOrIgnore($governorates);
    }
}
