<?php

return [
    'single' => [
        'label' => 'Жою',
        'modal' => [
            'heading' => ':label жою',
            'actions' => [
                'delete' => [
                    'label' => 'Жою',
                ],
            ],
        ],
        'notifications' => [
            'deleted' => [
                'title' => 'Жойылды',
            ],
        ],
    ],
    'multiple' => [
        'label' => 'Таңдалғандарды жою',
        'modal' => [
            'heading' => 'Таңдалған :label жою',
            'actions' => [
                'delete' => [
                    'label' => 'Жою',
                ],
            ],
        ],
        'notifications' => [
            'deleted' => [
                'title' => 'Жойылды',
            ],
            'deleted_partial' => [
                'title' => ':total ішінен :count жойылды',
                'missing_authorization_failure_message' => ':count жазбаны жоюға құқығыңыз жоқ.',
                'missing_processing_failure_message' => ':count жазбаны жою мүмкін болмады.',
            ],
            'deleted_none' => [
                'title' => 'Жою сәтсіз аяқталды',
                'missing_authorization_failure_message' => ':count жазбаны жоюға құқығыңыз жоқ.',
                'missing_processing_failure_message' => ':count жазбаны жою мүмкін болмады.',
            ],
        ],
    ],
];
