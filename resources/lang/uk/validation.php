<?php

declare(strict_types=1);

return [

    /*
     |--------------------------------------------------------------------------
     | Мовні ресурси перевірки введення
     |--------------------------------------------------------------------------
     |
     | Наступні ресурси містять стандартні повідомлення перевірки коректності
     | введення даних. Деякі з цих правил мають декілька варіантів, як,
     | наприклад, size. Ви можете змінити будь-яке з цих повідомлень.
     |
     */

    'accepted' => 'Ви повинні прийняти :attribute.',
    'activeUrl' => 'Поле :attribute не є правильним URL.',
    'after' => 'Поле :attribute має містити дату не раніше :date.',
    'after_or_equal' => 'Поле :attribute має містити дату не раніше або дорівнюватися :date.',
    'alpha' => 'Поле :attribute має містити лише літери.',
    'alphaDash' => 'Поле :attribute має містити лише літери, цифри та підкреслення.',
    'alphaNum' => 'Поле :attribute має містити лише літери та цифри.',
    'array' => 'Поле :attribute має бути масивом.',
    'before' => 'Поле :attribute має містити дату не пізніше :date.',
    'before_or_equal' => 'Поле :attribute має містити дату не пізніше, або дорівнюватися :date.',
    'between' => [
        'numeric' => 'Поле :attribute має бути між :min та :max.',
        'file' => 'Розмір файлу в полі :attribute має бути не менше :min та не більше :max кілобайт.',
        'string' => 'Текст в полі :attribute має бути не менше :min та не більше :max символів.',
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
    ],
    'boolean' => 'Поле :attribute повинне містити логічний тип.',
    'confirmed' => 'Поле :attribute не збігається з підтвердженням.',
    'date' => 'Поле :attribute не є датою.',
    'date_format' => 'Поле :attribute не відповідає формату :format.',
    'declined_if' => 'Поле :attribute має бути відхилено, якщо :other є :value.',
    'different' => 'Поля :attribute та :other повинні бути різними.',
    'digits' => 'Довжина цифрового поля :attribute повинна дорівнювати :digits.',
    'dimensions' => 'Поле :attribute містить неприпустимі розміри зображення.',
    'distinct' => 'Поле :attribute містить значення, яке дублюється.',
    'email' => 'Поле :attribute повинне містити коректну електронну адресу.',
    'ends_with' => 'Поле :attribute повинно закінчуватися одним із наступних розширень: :values.',
    'file' => 'Поле :attribute має містити файл.',
    'filled' => "Поле :attribute є обов'язковим для заповнення.",
    'exists' => 'Вибране для :attribute значення не коректне.',
    'extensions' => 'Файл у полі :attribute повинен мати одне з наступних розширень: :values.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'Поле :attribute має містити зображення.',
    'in' => 'Вибране для :attribute значення не коректне.',
    'inArray' => 'Значення поля :attribute не міститься в :other.',
    'integer' => 'Поле :attribute має містити ціле число.',
    'ip' => 'Поле :attribute має містити IP адресу.',
    'ipv4' => 'Поле :attribute має містити IPv4 адресу.',
    'ipv6' => 'Поле :attribute має містити IPv6 адресу.',
    'json' => 'Дані поля :attribute мають бути в форматі JSON.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'Поле :attribute має бути не більше :max.',
        'file' => 'Файл в полі :attribute має бути не більше :max кілобайт.',
        'string' => 'Текст в полі :attribute повинен мати довжину не більшу за :max.',
        'array' => 'Поле :attribute повинне містити не більше :max елементів.',
    ],
    'mimes' => 'Поле :attribute повинне містити файл одного з типів: :values.',
    'mimetypes' => 'Поле :attribute повинне містити файл одного з типів: :values.',
    'min' => [
        'numeric' => 'Поле :attribute повинне бути не менше :min.',
        'file' => 'Розмір файлу в полі :attribute має бути не меншим :min кілобайт.',
        'string' => 'Текст в полі :attribute повинен містити не менше :min символів.',
        'array' => 'Поле :attribute повинне містити не менше :min елементів.',
    ],
    'numeric' => 'Поле :attribute повинно містити число.',
    'phone' => 'Поле має бути дійсним номером телефону з мінімум :min цифрами, без пробілів та крапок, наприклад: +380555555555.',
    'phone.duplicates' => 'Дозволяється лише один телефонний номер типу \':type\'',
    'present' => 'Поле :attribute повинне бути присутнє.',
    'regex' => 'Поле :attribute має хибний формат.',
    'required' => "Поле ':attribute' є обов'язковим для заповнення.",
    'required_if' => "Поле :attribute є обов'язковим для заповнення, коли :other є рівним :value.",
    'required_unless' => "Поле :attribute є обов'язковим, якщо :other не вказано у :values.",
    'required_with' => "Поле :attribute є обов'язковим для заповнення, коли :values вказано.",
    'prohibited' => 'Поле :attribute заборонено.',
    'prohibited_if' => 'Поле :attribute заборонено, якщо :other дорівнює :value.',
    'prohibited_unless' => "Поле :attribute заборонено, якщо :other не є одним із значень: :values.",
    'same' => 'Поля :attribute та :other мають співпадати.',
    'size' => [
        'numeric' => 'Поле :attribute має бути довжини :size.',
        'file' => 'Файл в полі :attribute має бути розміром :size кілобайт.',
        'string' => 'Текст в полі :attribute повинен містити :size символів.',
        'array' => 'Поле :attribute повинне містити :size елементів.',
    ],
    'string' => 'Поле :attribute повинне містити текст.',
    'timezone' => 'Поле :attribute повинне містити коректну часову зону.',
    'unique' => 'Таке значення поля :attribute вже існує.',
    'email_already_exists' => 'Користувач з таким e-mail вже існує в системі. Якщо ви бажаєте додати йому нову посаду, будь ласка, скористайтеся функцією "Додати посаду" в профілі співробітника.',
    'uploaded' => 'Завантаження поля :attribute не вдалося.',
    'url' => 'Формат поля :attribute неправильний.',
    'uuid' => 'Поле :attribute повинно містити коректний UUID.',

    /*
    |--------------------------------------------------------------------------
    | Додаткові ресурси для перевірки введення
    |--------------------------------------------------------------------------
    |
    | Тут Ви можете вказати власні ресурси для підтвердження введення,
    | використовуючи формат "attribute.rule", щоб дати назву текстовим змінним.
    | Так ви зможете легко додати текст повідомлення для заданого атрибуту.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
            'firstName' => 'Прізвище',
        ],
        'unique_edrpou' => 'Заклад з таким ЄДРПОУ  вже зареєстровано в системі',
        ':attribute.required' => 'Поле :attribute є обов\'язковим для заповнення.',
        'roleTable' => 'Заповніть таблицю Ролі',
        'documentsEmpty' => 'Заповніть таблицю Документи Паспорт або Номер РНОКПП',
        'educationsTable' => 'Заповніть таблицю Освіта',
        'specialitiesTable' => 'Заповніть таблицю Спеціальності',
        'employeeTable' => 'Заповніть данні Працівника',
        'Token' => [
            'csrfToken' => 'Токен CSRF є недійсним.',
        ],
        'cipher' => [
            'initiator_differ_business' => 'Завантажений ключ електронного підпису не є ключем юридичної організації чи ФОП ',
            'initiator_differ_person' => 'Завантажений ключ електронного підпису не є ключем фізичної особи',
            'edrpouDiffer' => 'ЄДРПОУ ключа електронного підпису відрізняється від вказаного',
            'drfouDiffer' => 'ІПН ключа електронного підпису відрізняється від вказаного',
            'kepTimeExpired' => 'Термін дії ключа електронного підпису закінчився',
            'kepNotValid' => 'Завантажений ключ не може використовуватись для електронного підпису'
        ],
        'patient' => [
            'confidantPersonRequiredForChildren' => "Довірена особа є обов'язковою для дітей.",
            'confidantPersonRequiredForMinor' => "Довірена особа є обов'язковою для неповнолітніх пацієнтів.",
            'confidantPersonMustBeCapable' => 'Довіреною особою не може бути особа, яка має документ, що підтверджує її дієздатність.',
            'birthDocumentsRequired' => 'Документи повинні містити один з наступних документів: СВІДОЦТВО ПРО НАРОДЖЕННЯ, ЗАКОРДОННЕ СВІДОЦТВО ПРО НАРОДЖЕННЯ.',
            'personalDocumentsRequired' => 'Необхідно подати документ, що підтверджує персональні дані.'
        ]
    ],

    'employee' => [
        'birth_date_iso' => 'Дата народження має бути в форматі ISO 8601',
        'party' => [
            'birth_date_value' => 'Дата народження має бути пізніше 1900-01-01',
        ],
        'owner_date_mismatch' => 'Вказана дата народження не співпадає з наявною датою для цього користувача',
        'wrong_tax_id' => 'Для даного співробітника має бути вказаний його ІПН',
        'missed_tax_if' => 'Для даного співробітника не вказаний його ІПН'
    ],

    /*
    |--------------------------------------------------------------------------
    | Власні назви атрибутів
    |--------------------------------------------------------------------------
    |
    | Наступні правила дозволяють налаштувати заміну назв полів введення
    | для зручності користувачів. Наприклад, вказати "Електронна адреса" замість
    | "email".
    |
    */

    'attributes' => [
        'name' => 'ім\'я',
        'phone' => 'телефон',
        'password' => 'пароль',
        'keyContainerUpload' => 'контейнер ключа',
        'knedp' => 'КНЕДП',
        'Token' => 'токен CSRF',
        'edrpou' => 'ЄДРПОУ',
        'email' => 'E-mail',
        'contact.phones.*.number' => 'Телефон',
        'contact.phones.*.type' => 'Тип Номера',
        'contact.email' => 'E-mail',
        'type' => 'Тип спеціальності',
        'owner' => [
            'firstName' => 'Ім’я',
            'lastName' => 'Прізвище',
            'secondName' => 'По батькові',
            'birthDate' => 'Дата народження',
            'email' => 'E-mail',
            'gender' => 'Стать',
            'position' => 'Посада керівника НМП',
            'taxId' => 'РНОКПП',
            'documents' => [
                'type' => 'Тип документа',
                'number' => 'Серія/номер документа'
            ]
        ],
        // Party
        'party.lastName' => __('forms.last_name'),
        'party.firstName' => __('forms.first_name'),
        'party.secondName' => __('forms.second_name'),
        'party.gender' => __('forms.gender'),
        'party.birthDate' => __('forms.birth_date'),
        'party.taxId' => __('forms.tax_id'),
        'party.email' => __('forms.email'),
        'party.workingExperience' => __('forms.working_experience'),
        'party.aboutMyself' => __('forms.about_myself'),

        // Phones (nested under party)
        'party.phones' => __('forms.phones'),
        'party.phones.*.type' => __('forms.phone_type'),
        'party.phones.*.number' => __('forms.phone_number'),

        'party.documents.*.number' => 'Серія/номер документа',

        'documents.*.type' => __('forms.document_type'),
        'documents.*.number' => __('forms.document_number'),
        'documents.*.issuedBy' => __('forms.document_issued_by'),
        'documents.*.issuedAt' => __('forms.document_issued_at'),
        'form.documents.min' => 'Необхідно додати хоча б один документ, що посвідчує особу.',
        'form.doctor.educations.min' => 'Для ролі лікаря необхідно заповнити розділ "Освіта".',
        'form.doctor.specialities.min' => 'Для ролі лікаря необхідно заповнити розділ "Спеціальності".',
        'form.party.email.unique' => 'Користувач з такою поштою вже зареєстрований у цій мед. організації.',
        'form.documents' => __('forms.documents'),
        'form.doctor.educations' => __('forms.education'),
        'form.doctor.specialities' => __('forms.specialities'),
        'form.doctor.qualifications' => __('forms.qualifications'),
        'form.doctor.scienceDegrees' => __('forms.science_degree'),

        // Поля всередині розділу "Освіта"
        'doctor.educations.*.city' => __('forms.city'),
        'doctor.educations.*.institutionName' => __('forms.institutionName'),

        // Поля всередині розділу "Спеціальності"
        'doctor.specialities.*.attestationName' => __('forms.issued_by'),
        'doctor.specialities.*.level' => __('forms.speciality_level'),

        // Position
        'position' => __('forms.position'),
        'employeeType' => __('forms.employee_type'),

        // Doctor Specific
        'doctor.specialities' => __('forms.specialities'),
        'doctor.educations' => __('forms.education'),
        'doctor.qualifications' => __('forms.qualifications'),
        'doctor.scienceDegrees' => __('forms.science_degree'),

        'divisionId' => __('forms.division'),
        'patient' => [
            'firstName' => 'ім’я',
            'lastName' => 'прізвище',
            'secondName' => 'по батькові',
            'birthDate' => 'дата народження',
            'birthCountry' => 'країна народження',
            'birthSettlement' => 'місто народження',
            'gender' => 'стать',
            'email' => 'E-mail',
            'unzr' => 'УНЗР',
            'noTaxId' => 'РНОКПП/ІПН відсутній',
            'taxId' => 'номер РНОКПП',
            'secret' => 'кодове слово',

            'emergencyContact' => [
                'firstName' => 'ім’я',
                'lastName' => 'прізвище',
                'secondName' => 'по батькові'
            ],
        ],
        'patient.phones.*.type' => 'тип телефону',
        'patient.phones.*.number' => 'номер телефону',
        'patient.emergencyContact.phones.*.type' => 'тип телефону',
        'patient.emergencyContact.phones.*.number' => 'номер телефону',
        'patient.authenticationMethods.*' => [
            'type' => 'метод автентифікації',
            'phoneNumber' => 'номер телефону',
            'value' => 'законний представник пацієнта',
            'alias' => 'роль'
        ],

        'authenticationMethod.type' => 'тип автентифікації',

        'addresses' => [
            'area' => 'область',
            'settlement' => 'місто',
            'streetType' => 'тип вулиці',
            'street' => 'назва вулиці',
            'building' => 'будинок',
            'apartment' => 'квартира',
            'zip' => 'поштовий індекс'
        ],
        'documentsRelationship.*' => [
            'type' => 'тип документа',
            'number' => 'серія/номер документа',
            'issuedBy' => 'орган яким виданий документ',
            'issuedAt' => 'дата видачі документа',
            'activeTo' => 'дійсний до'
        ],
        'documents' => 'документ, що засвідчує особу',
        'documents.*' => [
            'type' => 'тип документа',
            'number' => 'серія/номер документа',
            'issuedBy' => 'орган яким виданий документ',
            'issuedAt' => 'дата видачі документа',
            'expirationDate' => 'дійсний до'
        ],
        'patientsFilter' => [
            'firstName' => 'ім’я',
            'lastName' => 'прізвище',
            'birthDate' => 'дата народження',
            'secondName' => 'по батькові',
            'taxId' => 'РНОКПП(ІПН)',
            'phoneNumber' => 'номер телефону',
            'birthCertificate' => 'свідоцтво про народження'
        ],
        'document' => [
            'type' => 'Тип документа',
            'number' => 'Серія/номер документа',
            'issuedBy' => 'Орган яким виданий документ',
            'issuedAt' => 'Дата видачі документа',
            'expirationDate' => 'дійсний до'
        ],
        'passportData' => [
            'firstName' => 'Ім’я',
            'lastName' => 'Прізвище',
            'secondName' => 'По батькові',
            'birthDate' => 'Дата народження',
            'email' => 'E-mail',
            'gender' => 'Стать',
            'position' => 'Посада керівника НМП',
            'taxId' => 'РНОКПП',
            'documents' => [
                'type' => 'Тип документа',
                'number' => 'Серія/номер документа'
            ]
        ],
        'owner.phones.*.number' => 'телефон',
        'owner.phones.*.type' => 'Тип Номера',
        'country' => 'Країна',
        'region' => 'Область',
        'area' => 'Район',
        'settlement' => 'Населений пункт',
        'settlementType' => 'Тип населеного пункту',
        'streetType' => 'Тип вулиці',
        'street' => 'Вулиця',
        'building' => 'Будинок',
        'apartment' => 'Квартира',
        'zipCode' => 'Поштовий індекс',
        'location' => [
            'latitude' => 'Широта',
            'longitude' => 'Довгота',
        ],
        'division' => [
            'name' => 'Назва',
            'type' => 'Тип',
            'email' => 'E-mail',
            'phones.number' => 'Телефон',
            'phones.type' => 'Тип Номера',
            'location.latitude' => 'Широта',
            'location.longitude' => 'Довгота',
        ],
        'division.phones.*.number' => 'Телефон',
        'division.phones.*.type' => 'Тип Номера',
        'division.location.latitude' => 'Широта',
        'division.location.longitude' => 'Довгота',

        // Healthcare Service
        'category.coding.*.code' => 'категорія послуги',
        'type.coding.*.code' => 'тип медичної послуги',
        'specialityType' => 'лікарська спеціальність',
        'providingCondition' => 'Умови надання послуг',
        'licenseId' => 'ліцензія закладу',
        'comment' => 'коментар',
        'notAvailable.*.during.start' => 'початок робочого часу',
        'notAvailable.*.during.end' => 'кінець робочого часу',
        'notAvailable.*.description' => 'коментар до неробочого часу',

        'healthcareService' => [
            'constraint' => [
                'typeAndCondition' => "Комбінація 'місце надання послуг', 'лікарська спеціальність' та 'умови надання послуги' мають бути унікальні",
                'categoryAndType' => "Комбінація 'місце надання послуг', 'категорія послуги' та 'тип медичної послуги' мають бути унікальні",
                'categoryPharmacy' => 'Категорія PHARMACY вже використовується у цьому місці надання послуг'
            ]
        ],

        'educations' => [
            'degree' => 'Ступінь',
            'speciality' => 'Спеціальність',
            'institutionName' => 'Назва закладу',
            'country' => 'Країна',
            'city' => 'Місто',
            'institutionType' => 'Тип закладу',
            'specialityType' => 'Тип спеціальності',
            'instituteType' => 'Тип закладу',
            'specialityLevel' => 'Рівень спеціальності',
            'diplomaNumber' => 'Номер диплому',
        ],
        'education' => [
            'degree' => 'Ступінь',
            'speciality' => 'Спеціальність',
            'institutionName' => 'Назва закладу',
            'country' => 'Країна',
            'city' => 'Місто',
            'institutionType' => 'Тип закладу',
            'specialityType' => 'Тип спеціальності',
            'instituteType' => 'Тип закладу',
            'specialityLevel' => 'Рівень спеціальності',
            'diplomaNumber' => 'Номер диплому',
        ],
        'contractType' => 'Тип договору',
        'contractorPaymentDetails' => [
            'mfo' => 'МФО',
            'bankName' => 'Назва банку',
            'payerAccount' => 'IBAN',
        ],
        'startDate' => 'Дата початку',
        'endDate' => 'Дата завершення',
        'status' => 'Статус',
        'contractorRmspAmount' => 'Кількість населення',
        'contractorBase' => 'На якій підставі діє підписант',
        'statuteMd5' => 'Статут',
        'additionalDocumentMd5' => 'Додатковий документ',
        'contractorDivisions' => 'Місця надання послуг',
        'externalContractors' => [
            'contract' => [
                'number' => 'Номер договору з субпідрядником',
                'issuedAt' => 'Дата початку договору',
                'expiresAt' => 'Дата закінчення договору',

            ],
            'legalEntity' => [
                'name' => 'Медична організація',

            ],
            'divisions' => [
                'name' => 'Назва Підрозділу',
                'medicalService' => 'Медична послуга'
            ]

        ],

        'party.documents' => 'Документи',

        // Form fields
        'form.doctor.educations.0.city' => 'Освіта (місто)',
        'form.doctor.educations.0.institutionName' => 'Освіта (назва закладу)',
        'form.doctor.specialities.0.attestationName' => 'Спеціалізація (назва атестації)',
        'form.doctor.specialities.0.level' => 'Рівень спеціалізації',
        'form.doctor.qualifications.0.institutionName' => 'Кваліфікація (назва закладу)',
        'form.doctor.scienceDegrees.0.city' => 'Науковий ступінь (місто)',
        'form.doctor.scienceDegrees.0.institutionName' => 'Науковий ступінь (назва закладу)',

        // Documents
        'documents.*.expirationDate' => 'Дійсний до',

        // Licence
        'issuedBy' => 'ким видано ліцензію',
        'licenseNumber' => 'Серія та/або номер ліцензії',
        'issuedDate' => 'дата видачі ліцензії',
        'activeFromDate' => 'дата початку дії ліцензії',
        'orderNo' => 'номер наказу',
        'expiryDate' => 'дата завершення дії ліцензії',
        'whatLicensed' => 'напрям діяльності, що ліцензовано',

        'uploadedDocuments.*' => 'для завантаження файлів',
        'verificationCode' => 'код підтвердження з СМС',
        'encounter' => [
            'actions' => 'дії',
            'diagnoses' => 'діагнози',
            'division.identifier.value' => 'місце надання послуг',
            'class.code' => 'клас взаємодії',
            'type.coding.*.code' => 'тип взаємодії',
            'period' => [
                'start' => 'час початку',
                'end' => 'час закінчення'
            ],
            'priority.coding.*.code' => 'пріоритет',
            'reasons' => 'причини звернення',
            'diagnoses.role.coding.*.code' => 'тип',
            'diagnoses.rank' => 'пріоритет'
        ],
        'conditions' => 'діагнози',
        'conditions.*.reportOrigin.coding.*.code' => 'джерело інформації',
        'conditions.*.code.coding.0.code' => 'код стану за ICPC-2',
        'conditions.*.code.coding.1.code' => 'код стану за МКХ-10',
        'conditions.*.onsetDate' => 'дата початку',
        'conditions.*.onsetTime' => 'час початку',
        'conditions.*.assertedDate' => 'дата внесення',
        'conditions.*.assertedTime' => 'час внесення',
        'conditions.*.clinicalStatus' => 'клінічний статус',
        'conditions.*.verificationStatus' => 'статус верифікації',
        'conditions.*.severity.coding.*.code' => 'ступінь тяжкості стану',
        'episode' => [
            'name' => 'назва епізоду',
            'type' => [
                'code' => 'тип епізоду'
            ]
        ],
        'immunizations' => [
            'primarySource' => 'джерело інформації',
            'performer' => 'виконавець',
            'reportOrigin' => 'пацієнт',
            'notGiven' => 'чи була проведена',
            'explanation.reasons' => 'причини проведення',
            'explanation.reasons.*.coding.*.code' => 'причини',
            'explanation.*.reasonsNotGiven.coding.*.code' => 'причини',
            'vaccineCode.coding.*.code' => 'код та назва',
            'manufacturer' => 'виробник',
            'lotNumber' => 'серія',
            'expirationDate' => 'дата закінчення придатності',
            'doseQuantity.value' => 'кількість введеної вакцини',
            'doseQuantity.unit' => 'одиниця вимірювання',
            'site' => 'частина тіла',
            'site.coding.*.code' => 'частина тіла',
            'route' => 'шлях введення',
            'route.coding.*.code' => 'шлях введення',
            'vaccinationProtocols.*.doseSequence' => 'порядковий номер дози',
            'vaccinationProtocols.*.authority' => 'автор протоколу',
            'vaccinationProtocols.*.series' => 'етап імунізації',
            'vaccinationProtocols.*.seriesDoses' => 'кількість доз по протоколу',
            'vaccinationProtocols.*.targetDiseases' => 'протидія загрозам',
            'explanation.reasonsNotGiven' => 'причини не проведення',
            'date' => 'дата вакцинації',
            'time' => 'час вакцинації',
        ],
        'observations.*' => [
            'primarySource' => 'джерело інформації',
            'performer' => 'виконавець',
            'reportOrigin' => 'інше джерело',
            'categories.coding.*.code' => 'категорія',
            'valueQuantity.value' => 'значення',
            'valueCodeableConcept' => 'значення',
            'valueString' => 'значення',
            'valueBoolean' => 'значення',
            'valueDateTime' => 'значення',
            'comment' => 'коментар'
        ],
        'errors' => [
            'email' => 'Неправильний формат електронної адреси',
            'wrongNumberFormat' => 'Неправильний формат номеру',
            'expiryDateGreat' => 'Дата не може бути більше поточної дати',
            'expiryDateLess' => 'Дата не може бути менше дати початку',
            'expiryDateLessNow' => 'Дата не може бути менше поточної дати',
            'invalidNationalId' => 'Номер паспорта має бути: або 2 літери та 6 цифр, або 9 цифр',
            'invalidTaxId' => 'Ідентифікаційний номер повинен містити рівно 10 цифр',
            'date_iso' => 'Дата має бути в форматі ISO 8601',
            'wrongFieldFormat' => 'Поле має хибний формат',
            'wrongSymbols' => 'Поле містить недопустимі символи',
            'nonEmpty' => 'Наразі поле не може бути пустим',
            'minLen2' => 'Мінімальна довжина - 2 символи',
            'minLen3' => 'Мінімальна довжина - 3 символи',
            'onlyNumeric' => 'Дозволено лише цифри',
            'onlyCyrillic' => 'Дозволено лише кирилічні символи',
            'onlyLatin' => 'Дозволено лише латинські символи',
            'onlyNumericLatin' => 'Дозволено лише цифри та латинські символи',
            'requiredField' => 'Це поле є обов\'язковим до заповнення',
            'ownerAge' => 'Вік власника має бути не менше 18 років',
            'numberExist' => 'Такий номер вже існує',
            'requiredFirstName' => 'Iм\'я є обов\'язковим до заповнення',
            'requiredLastName' => 'Прізвище є обов\'язковим до заповнення',
            'requiredBirthDate' => __('Дата народження є обов\'язковою до заповнення'),
            'requiredContactPhone' => __('Контактний телефон є обов\'язковим до заповнення'),
            'requiredTaxId' => __('Номер ІПН чи РНОКПП є обов\'язковим до заповнення'),
            'requiredDocumentType' => __('Тип документа є обов\'язковим до заповнення'),
            'requiredPostion' => __('Посада є обов\'язковою до заповнення'),
            'requiredEmail' => __('Поле :attribute вже зареєстровано в системі'),
            'requiredPhone' => __('Поле з номерами телефонів є обов\'язковим'),
            'requiredPhoneArray' => __('Поле з номерами телефонів повинно бути масивом'),
            'requiredPhoneNumber' => __('Номер телефону є обов\'язковим'),
            'requiredPhoneNumberMax' => __('Номер телефону повинен містити 12 цифр'),
            'requiredPhoneType' => __('Тип телефону є обов\'язковим'),
            'requiredPhoneTypeSpeciality' => __('Тип телефону повинен бути "МОБІЛЬНИЙ" або "СТАЦІОНАРНИЙ"'),
            'requiredCategory' => __('Категорія є обов\'язковою до заповнення'),
            'requiredOrderNumber' => __('Номер наказу є обов\'язковим до заповнення'),
            'requiredOrderDate' => __('Дата наказу є обов\'язковою до заповнення'),
            'requiredIssuedDate' => __('Дата видачі є обов\'язковою до заповнення'),
            'requiredActiveFromDate' => __('Дата початку дії є обов\'язковою до заповнення'),
            'requiredIssuedBy' => __('Потрібно вказати орган, який видав документ'),
        ],
        'diagnosticReport' => [
            'category.*.coding.*.code' => 'категорія',
            'code.identifier.value' => 'послуги',
            'paperReferral.requisition' => 'номер',
            'paperReferral.requesterEmployeeName' => 'автор',
            'paperReferral.requesterLegalEntityEdrpou' => 'ЄДРПОУ закладу, що виписав',
            'paperReferral.requesterLegalEntityName' => 'Найменування закладу, що виписав',
            'paperReferral.serviceRequestDate' => 'дата',
            'conclusionCode.coding.*.code' => 'код заключення(за МКХ-10АМ)',
            'paperReferral.conclusion' => 'заключення',
            'paperReferral.note' => 'нотатки',
            'division.identifier.value' => 'місце надання послуг',
            'resultsInterpreter.reference.identifier.value' => 'лікар, що інтерпретував результати',
            'effectivePeriodStartDate' => 'дата початку прийому',
            'effectivePeriodStartTime' => 'час початку прийому',
            'effectivePeriodEndDate' => 'дата завершення прийому',
            'effectivePeriodEndTime' => 'час завершення прийому',
            'issuedDate' => 'дата внесення',
            'issuedTime' => 'час внесення',
            'resultsInterpreter.text' => 'лікар, що інтерпретував результати',
            'conclusion' => 'заключення'
        ],
        'procedures' => [
            'referralType' => 'тип направлення',
            'category.coding.*.code' => 'категорія',
            'code.identifier.value' => 'послуги',
            'paperReferral.requisition' => 'номер',
            'paperReferral.requesterEmployeeName' => 'автор',
            'paperReferral.requesterLegalEntityEdrpou' => 'ЄДРПОУ закладу, що виписав',
            'paperReferral.requesterLegalEntityName' => 'Найменування закладу, що виписав',
            'paperReferral.serviceRequestDate' => 'дата',
            'division.identifier.value' => 'місце надання послуг',
            'outcome.coding.*.code' => 'результат проведення',
            'performedPeriodStartTime' => 'дата початку процедури',
            'performedPeriodStartDate' => 'час початку процедури',
            'performedPeriodEndDate' => 'дата завершення процедури',
            'performedPeriodEndTime' => 'час завершення процедури',
            'note' => 'комент'
        ],

        // Declaration
        'authorizeWith' => 'метод автентифікації',
        'employeeId' => 'ПІБ лікаря',

        // Equipment
        'names.*.name' => 'назва медичного виробу',
        'names.*.type' => 'тип назви',
        'serialNumber' => 'серійний №',
        'recorder' => 'співробітник, що вносить дані',
        'inventoryNumber' => 'інвентарний №',
        'manufacturer' => 'виробник',
        'manufactureDate' => 'дата виробництва',
        'expirationDate' => 'термін придатності',
        'modelNumber' => '№ моделі',
        'lotNumber' => '№ закупівлі',
        'note' => 'коментар',
        'errorReason' => 'причина зміни статусу',
        'availabilityStatus' => 'доступність',
        'statusIncorrect' => 'Змініть статус доступності перед тим, як оновлювати статус обладнання на “Неактивний”. Поточний статус доступності обладнання - “Доступний”.'
    ]
];
