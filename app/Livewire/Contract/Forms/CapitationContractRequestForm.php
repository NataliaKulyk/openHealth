<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

class CapitationContractRequestForm extends BaseContractRequestForm
{
    public int $contractorRmspAmount;
    public bool $externalContractorFlag;
    public array $externalContractors;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'contractorRmspAmount' => ['required', 'integer:strict'],
            'externalContractorFlag' => ['nullable', 'boolean'],
            'externalContractors' => ['nullable', 'array'],
            'externalContractors.legalEntityId' => ['required', 'uuid', 'exists:legal_entities,uuid'],
            'externalContractors.contract' => ['required', 'array'],
            'externalContractors.contract.number' => ['required', 'string', 'max:255'],
            'externalContractors.contract.issuedAt' => ['required', 'date_format:d.m.Y'],
            'externalContractors.contract.expiresAt' => ['required', 'date_format:d.m.Y'],
            'externalContractors.divisions' => ['required', 'array'],
            'externalContractors.divisions.id' => ['required', 'uuid', 'exists:divisions,uuid'],
            'externalContractors.divisions.medicalService' => ['required'],
        ]);
    }
}
