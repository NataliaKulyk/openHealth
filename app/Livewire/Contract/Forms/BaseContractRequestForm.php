<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

abstract class BaseContractRequestForm extends Form
{
    public string $idForm;

    public string $startDate;

    public string $endDate;

    public string $contractNumber;

    public $statuteMd5;

    public $additionalDocumentMd5;

    public array $contractorPaymentDetails;

    public string $knedp;

    public TemporaryUploadedFile $keyContainerUpload;

    public string $password;

    /**
     * Base rules for both types of contract
     *
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'contractorPaymentDetails' => ['required', 'array'],
            'contractorPaymentDetails.payerAccount' => ['required', 'string', 'max:255'],
            'contractorPaymentDetails.MFO' => ['required', 'string', 'max:255'],
            'contractorPaymentDetails.bankName' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date'],
            'idForm' => ['required'],
            'contractNumber' => ['nullable', 'string', 'max:255'],
            'statuteMd5' => ['nullable', 'file'],
            'additionalDocumentMd5' => ['nullable', 'file'],
        ];
    }

    /**
     * List of rules for signing Cipher form.
     *
     * @return array[]
     */
    public function rulesForSigning(): array
    {
        return [
            'knedp' => ['required', 'string'],
            'password' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s']
        ];
    }
}
