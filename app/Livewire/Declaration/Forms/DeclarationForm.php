<?php

declare(strict_types=1);

namespace App\Livewire\Declaration\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class DeclarationForm extends Form
{
    public string $personId;

    public string $employeeId = '';

    public string $divisionId = '';

    public ?string $authorizeWith = null;

    public ?string $parentDeclarationId = null;

    public ?int $verificationCode = null;

    public array $uploadedDocuments;

    public string $knedp;

    public TemporaryUploadedFile $keyContainerUpload;

    public string $password;

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
        return ['verificationCode' => ['required', 'digits:4']];
    }

    public function rulesForUploadingDocuments(): array
    {
        return ['uploadedDocuments.*' => ['required', 'file', 'mimes:jpeg,jpg', 'max:10000']];
    }

    public function rulesForSigning(): array
    {
        return [
            'knedp' => ['required', 'string'],
            'password' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
        ];
    }
}
