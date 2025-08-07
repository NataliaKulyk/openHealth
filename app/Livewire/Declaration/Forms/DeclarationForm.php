<?php

declare(strict_types=1);

namespace App\Livewire\Declaration\Forms;

use Livewire\Form;

class DeclarationForm extends Form
{
    public string $personId;

    public string $employeeId = '';

    public string $divisionId = '';

    public ?string $authorizeWith = null;

    public ?string $parentDeclarationId = null;

    public ?int $verificationCode = null;

    public function rulesForCreating(): array
    {
        return [
            'personId' => ['required', 'uuid'],
            'employeeId' => ['required', 'uuid'],
            'divisionId' => ['required', 'uuid'],
            'authorizeWith' => ['nullable', 'uuid'],
            'parentDeclarationId' => ['nullable', 'uuid']
        ];
    }

    public function rulesForApproving(): array
    {
        return [
            'verificationCode' => ['required', 'digits:4']
        ];
    }
}
