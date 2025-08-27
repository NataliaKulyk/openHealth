<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Errors Language Lines
    |--------------------------------------------------------------------------
    */

    '404' => 'Помилка 404',
    '500' => 'Помилка 500',
    'goBack' => 'Повернутися назад',
    'home' => 'На головну',
    'oops' => 'Ой, щось пішло не так',
    'pageNotFound' => 'Сторінку не знайдено',
    'policy' => [
        'licence' => [
            'primary_not_editable'
        ]
    ],

    'ehealth' => [
        'validation_error_header' => 'eHealth відхилив запит через невірні дані. Будь ласка, виправте:',
        'messages' => [
            'required property' => 'відсутнє',
            'schema does not allow additional properties' => 'містить недійсні дані',
            'type mismatch' => 'невідповідність типу даних.',
            'does not match pattern' => 'не відповідає очікуваному формату.',
            'is not allowed for doctor' => 'даний тип кваліфікації не дозволено для лікаря.',
            'type mismatch. Expected integer but got string' => 'Невірний тип даних (очікувалося число)',
            'employee doesn\'t have speciality with active speciality_officio' => 'Відсутня основна спеціалізація',
            'speciality not allowed' => 'Невірна спеціалізація для лікаря',
            'Incorrect value.' => 'Некоректне значення.',
            'required' => 'відсутнє',
            'length' => 'необхідно додати хоча б один елемент',
            'speciality_not_allowed' => 'спеціалізація ":speciality" не дозволена для лікаря.',
            'speciality_officio_not_allowed' => 'спеціалізація ":speciality" з активним основним записом не дозволена для лікаря.',
        ]
    ],
];
