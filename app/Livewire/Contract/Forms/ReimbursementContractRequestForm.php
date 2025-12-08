<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

use App\Rules\InDictionary;
use Carbon\CarbonImmutable;

class ReimbursementContractRequestForm extends BaseContractRequestForm
{
    protected const int REIMBURSEMENT_CONTRACT_MAX_PERIOD_DAY = 1096;

    public ?string $previousRequestId = null;

    public ?array $medicalPrograms;

    public bool $consentText;

    public function rules(): array
    {
        $parentRules = parent::rules();

        $parentRules['endDate'][] = function ($attribute, $value, $fail) {
            $startDate = CarbonImmutable::parse($this->startDate);
            $endDate = CarbonImmutable::parse($value);

            if ($startDate->diffInDays($endDate) > self::REIMBURSEMENT_CONTRACT_MAX_PERIOD_DAY) {
                $fail(
                    'різниця між датою закінчення договору та датою початку договору '
                    . 'не повинна перевищувати ' . self::REIMBURSEMENT_CONTRACT_MAX_PERIOD_DAY . ' днів'
                );
            }
        };

        return array_merge($parentRules, [
            'idForm' => ['required', new InDictionary('REIMBURSEMENT_CONTRACT_TYPE')],

            'previousRequestId' => ['nullable', 'uuid', 'exists:contracts,uuid'],

            'medicalPrograms' => ['nullable', 'array'],
            'consentText' => ['accepted']
        ]);
    }
}
