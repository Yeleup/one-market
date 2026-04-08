<?php

return [
    'column_manager' => [
        'heading' => 'Бағандар',
        'actions' => [
            'apply' => [
                'label' => 'Бағандарды қолдану',
            ],
            'reset' => [
                'label' => 'Қалпына келтіру',
            ],
        ],
    ],
    'columns' => [
        'actions' => [
            'label' => 'Әрекет|Әрекеттер',
        ],
        'select' => [
            'loading_message' => 'Жүктелуде...',
            'no_options_message' => 'Қолжетімді нұсқалар жоқ.',
            'no_search_results_message' => 'Іздеуге сай нұсқа табылмады.',
            'placeholder' => 'Нұсқаны таңдаңыз',
            'searching_message' => 'Ізделуде...',
            'search_prompt' => 'Іздеу үшін тере бастаңыз...',
        ],
        'text' => [
            'actions' => [
                'collapse_list' => ':count-ке аз көрсету',
                'expand_list' => ':count-ке көп көрсету',
            ],
            'more_list_items' => 'тағы :count',
        ],
    ],
    'fields' => [
        'bulk_select_page' => [
            'label' => 'Жаппай әрекеттер үшін осы беттегі барлық элементті таңдау/алып тастау.',
        ],
        'bulk_select_record' => [
            'label' => 'Жаппай әрекеттер үшін :key элементін таңдау/алып тастау.',
        ],
        'bulk_select_group' => [
            'label' => 'Жаппай әрекеттер үшін :title тобын таңдау/алып тастау.',
        ],
        'search' => [
            'label' => 'Іздеу',
            'placeholder' => 'Іздеу',
            'indicator' => 'Іздеу',
        ],
    ],
    'summary' => [
        'heading' => 'Қорытынды',
        'subheadings' => [
            'all' => 'Барлық :label',
            'group' => ':group қорытындысы',
            'page' => 'Осы бет',
        ],
        'summarizers' => [
            'average' => [
                'label' => 'Орташа',
            ],
            'count' => [
                'label' => 'Саны',
            ],
            'sum' => [
                'label' => 'Жиыны',
            ],
        ],
    ],
    'actions' => [
        'disable_reordering' => [
            'label' => 'Сұрыптауды аяқтау',
        ],
        'enable_reordering' => [
            'label' => 'Жазбаларды қайта реттеу',
        ],
        'filter' => [
            'label' => 'Сүзгі',
        ],
        'group' => [
            'label' => 'Топтау',
        ],
        'open_bulk_actions' => [
            'label' => 'Жаппай әрекеттер',
        ],
        'column_manager' => [
            'label' => 'Бағандарды басқару',
        ],
    ],
    'empty' => [
        'heading' => ':model табылмады',
        'description' => 'Бастау үшін :model құрыңыз.',
    ],
    'filters' => [
        'actions' => [
            'apply' => [
                'label' => 'Сүзгілерді қолдану',
            ],
            'remove' => [
                'label' => 'Сүзгіні алып тастау',
            ],
            'remove_all' => [
                'label' => 'Барлық сүзгіні алып тастау',
                'tooltip' => 'Барлық сүзгіні алып тастау',
            ],
            'reset' => [
                'label' => 'Қалпына келтіру',
            ],
        ],
        'heading' => 'Сүзгілер',
        'indicator' => 'Белсенді сүзгілер',
        'multi_select' => [
            'placeholder' => 'Барлығы',
        ],
        'select' => [
            'placeholder' => 'Барлығы',
            'relationship' => [
                'empty_option_label' => 'Жоқ',
            ],
        ],
        'trashed' => [
            'label' => 'Жойылған жазбалар',
            'only_trashed' => 'Тек жойылған жазбалар',
            'with_trashed' => 'Жойылғандармен бірге',
            'without_trashed' => 'Жойылғандарсыз',
        ],
    ],
    'grouping' => [
        'fields' => [
            'group' => [
                'label' => 'Топтау',
            ],
            'direction' => [
                'label' => 'Топтау бағыты',
                'options' => [
                    'asc' => 'Өсу ретімен',
                    'desc' => 'Кему ретімен',
                ],
            ],
        ],
    ],
    'reorder_indicator' => 'Жазбаларды ретке келтіру үшін сүйреп апарыңыз.',
    'selection_indicator' => [
        'selected_count' => '1 жазба таңдалды|:count жазба таңдалды',
        'actions' => [
            'select_all' => [
                'label' => 'Барлық :count таңдау',
            ],
            'deselect_all' => [
                'label' => 'Барлығынан таңдауды алып тастау',
            ],
        ],
    ],
    'sorting' => [
        'fields' => [
            'column' => [
                'label' => 'Сұрыптау',
            ],
            'direction' => [
                'label' => 'Сұрыптау бағыты',
                'options' => [
                    'asc' => 'Өсу ретімен',
                    'desc' => 'Кему ретімен',
                ],
            ],
        ],
    ],
    'default_model_label' => 'жазба',
];
