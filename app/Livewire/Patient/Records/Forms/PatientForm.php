<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Records\Forms;

use Livewire\Form;

class PatientForm extends Form
{
    public array $authenticationMethod;

    public string $action;

    public function rulesForDeactivate(): array
    {
        return [
            'action' => ['required', 'string', 'in:DEACTIVATE'],
            'authenticationMethod' => ['required', 'array'],
            'authenticationMethod.id' => ['required', 'uuid']
        ];
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:INSERT'],
            'authenticationMethod' => ['required', 'array'],
            'authenticationMethod.type' => ['required', 'string'],
            'authenticationMethod.phoneNumber' => [
                'required_if:authenticationMethod.type,OTP',
                'regex:/^\+38[0-9]{10}$/'
            ]
        ];
    }
}
