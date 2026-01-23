<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Declarations Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are for various messages related to declarations,
    | e.g., declaration search, declaration-related API request messages, etc.,
    |
    */

    'search' => 'Пошук декларації',
    'count_active' => 'Кількість активних декларацій',
    'show' => 'Показувати',
    'requests' => 'Заявки на декларації',
    'active' => 'Активні декларації',
    'cancelled' => 'Відмінені декларації',
    'continue' => 'Продовжити створення декларації',
    'delete' => 'Видалити заявку на декларацію',
    'number' => 'Номер декларації',
    'application_for_registration_of_declaration' => 'Заявка на реєстрацію декларації',
    'create_an_application' => 'Створити заявку',
    'confirmation_of_application_for_registration_of_declaration' => 'Підтвердження заявки на реєстрацію декларації',
    'patient_confirm_information_message' => 'інформація з пам\'ятки пацієнта повідомлена пацієнту або його законному представнику',
    'confirmation_of_patient_signature_on_declaration_application' => 'Підтвердження підписання заявки на декларацію пацієнтом',
    'print_declaration_instruction' => 'Роздрукуйте заявку на декларацію в двох екземплярах з метою перевірки та підписання пацієнтом або його законним представником',
    'print_application' => 'Надрукувати заявку',
    'signed_confirmation' => 'Підтвердіть, що заявка на декларацію підписана пацієнтом або його законним представником',
    'patient_signed_declaration' => 'Декларація про вибір лікаря, який надає первинну медичну допомогу підписана пацієнтом',
    'approve' => 'Підтвердити декларацію',
    'reject_declaration_request' => 'Скасувати заявку на декларацію',
    'sign' => 'Підписати декларацію',
    'label' => 'Декларація',
    'id' => 'Ідентифікатор декларації',
    'start_date' => 'Дата подання декларації',
    'end_date' => 'Дата кінцевої дії декларації',
    'change_reason_if_exist' => 'Причина зміни статусу декларації (за наявності)',
    'change_reason_description_if_exist' => 'Опис причини зміни статусу (за наявності)',
    'method_of_filling_declaration' => 'Спосіб подання декларації',
    'print_declaration' => 'Надрукувати декларацію',

    'sync' => [
        'started' => 'Синхронізація декларацій запущена у фоновому режимі',
        'completed' => 'Синхронізація декларацій успішно завершена',
        'failed' => 'Синхронізація декларацій не вдалася',
    ],

    'status' => [
        'label' => 'Статус декларації',
        'draft' => 'Чернетка',
        'new' => 'Нова',
        'approved' => 'Не підписана',
        'signed' => 'Підписана',
        'active' => 'Активна',
        'rejected' => 'Відхилена',
        'cancelled' => 'Відмінена',
        'terminated' => 'Протермінована'
    ],

    // https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/1681555613/Declaration#%D0%A0%D0%B5%D0%BA%D0%BE%D0%BC%D0%B5%D0%BD%D0%B4%D0%BE%D0%B2%D0%B0%D0%BD%D1%96-%D0%BF%D0%B5%D1%80%D0%B5%D0%BA%D0%BB%D0%B0%D0%B4%D0%B8-%D0%B0%D1%82%D1%80%D0%B8%D0%B1%D1%83%D1%82%D1%83-reason
    'reason' => [
        'manual_employee_deactivate' => 'звільнення лікаря адміністративним персоналом закладу',
        'auto_employee_deactivate' => 'закриття закладу, звільнення лікаря або інший процес, пов’язаний із закладом',
        'auto_merge' => 'усунення дублюючого запису про пацієнта',
        'auto_death_registration' => 'присутність пацієнта в реєстрі померлих',
        'auto_fraud' => 'виявлено шахрайські дії',
        'auto_reorganization' => 'проведення реорганізації закладу та звільнення лікаря',
        'auto_deactivation_legal_entity' => 'автоматична деактивація закладу НМП',
        'manual_person' => 'об\'єдання записів з іншим пацієнтом',
        'auto_majority' => 'деактивація декларації з педіатром при досягненні пацієнтом 18-річного віку',
        'no_tax_id' => 'пацієнтом не надано РНОКПП при укладенні декларації',
        'offline' => 'пацієнтом укладено декларацію за документами (offline)',
        'auto_closing' => 'закінчення строку дії декларації'
    ],
];
