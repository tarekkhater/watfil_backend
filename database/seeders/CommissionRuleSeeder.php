<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        CommissionRule::firstOrCreate(
            ['trigger' => 'order_completed', 'name' => 'عمولة الطلب الافتراضية'],
            [
                'calculation_type' => 'percentage',
                'amount' => 5,
                'priority' => 100,
                'is_active' => true,
                'metadata' => [
                    'exempt_sources' => ['referral', 'internal'],
                ],
            ]
        );
    }
}
