<?php

return [
    'grace_period_days'       => (int) env('INSTALLMENT_GRACE_PERIOD_DAYS', 3),
    'penalty_amount'          => (float) env('INSTALLMENT_PENALTY_AMOUNT', 50),
    'reminder_days_before'    => [3, 1],
    'defaulted_overdue_count' => (int) env('INSTALLMENT_DEFAULTED_OVERDUE_COUNT', 3),
];
