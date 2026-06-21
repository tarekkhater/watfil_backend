<?php

return [
    'max_stages' => 7,

    'purification_systems' => [
        ['value' => 'ro', 'label_ar' => 'RO (تناضح عكسي)'],
        ['value' => 'uf', 'label_ar' => 'UF (ترشيح فائق)'],
        ['value' => 'carbon', 'label_ar' => 'فحم نشط'],
        ['value' => 'multi_stage', 'label_ar' => 'متعدد المراحل'],
        ['value' => 'other', 'label_ar' => 'أخرى'],
    ],

    'stages_counts' => [1, 2, 3, 4, 5, 6, 7],

    'primary_problem_types' => [
        ['value' => 'water_quality', 'label_ar' => 'جودة المياه'],
        ['value' => 'low_pressure', 'label_ar' => 'ضغط منخفض'],
        ['value' => 'leak', 'label_ar' => 'تسريب'],
        ['value' => 'noise', 'label_ar' => 'ضوضاء'],
        ['value' => 'filter_change', 'label_ar' => 'تغيير فلاتر'],
        ['value' => 'installation', 'label_ar' => 'تركيب'],
        ['value' => 'other', 'label_ar' => 'أخرى'],
    ],

    'malfunction_types' => [
        ['value' => 'stage_1', 'label_ar' => 'عطل المرحلة الأولى'],
        ['value' => 'stage_2', 'label_ar' => 'عطل المرحلة الثانية'],
        ['value' => 'stage_3', 'label_ar' => 'عطل المرحلة الثالثة'],
        ['value' => 'pump', 'label_ar' => 'عطل الموتور/المواسير'],
        ['value' => 'tank', 'label_ar' => 'عطل الخزان'],
        ['value' => 'electrical', 'label_ar' => 'عطل كهربائي'],
        ['value' => 'other', 'label_ar' => 'أخرى'],
    ],
];
