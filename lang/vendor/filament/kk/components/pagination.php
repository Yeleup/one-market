<?php

return [
    'label' => 'Беттер бойынша навигация',
    'overview' => '{1} 1 нәтиже көрсетілді|[2,*] :total нәтижеден :first - :last көрсетілді',
    'fields' => [
        'records_per_page' => [
            'label' => 'Бір бетте',
            'options' => [
                'all' => 'Барлығы',
            ],
        ],
    ],
    'actions' => [
        'first' => [
            'label' => 'Бірінші',
        ],
        'go_to_page' => [
            'label' => ':page-бетке өту',
        ],
        'last' => [
            'label' => 'Соңғы',
        ],
        'next' => [
            'label' => 'Келесі',
        ],
        'previous' => [
            'label' => 'Алдыңғы',
        ],
    ],
];
