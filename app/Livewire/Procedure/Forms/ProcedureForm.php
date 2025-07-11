<?php

declare(strict_types=1);

namespace App\Livewire\Procedure\Forms;

use App\Rules\InDictionary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class ProcedureForm extends Form
{
    public array $procedures;

    protected function rules(): array
    {
        return [
            'basedOn' => ['required_without:paperReferral'],
            'paperReferral' => ['required_without:basedOn'],
            'paperReferral.requisition' => ['nullable', 'string', 'max:255'],
            'paperReferral.requesterEmployeeName' => ['nullable', 'string', 'max:255'],
            'paperReferral.requesterLegalEntityEdrpou' => [
                Rule::requiredIf(data_get($this->procedures, 'referralType') === 'paper'),
                'regex:/^[0-9]{8,10}$/',
                'string',
                'max:255'
            ],
            'paperReferral.requesterLegalEntityName' => [
                Rule::requiredIf(data_get($this->procedures, 'referralType') === 'paper'),
                'string',
                'max:255'
            ],
            'paperReferral.serviceRequestDate' => [
                Rule::requiredIf(data_get($this->procedures, 'referralType') === 'paper'),
                'date'
            ],
            'paperReferral.note' => ['nullable', 'string', 'max:255'],
            'code.identifier.value' => ['required', 'uuid', 'max:255'],
            'performedPeriod.start' => ['required', 'date', 'before_or_equal:now'],
            'performedPeriod.end' => ['required', 'date', 'before_or_equal:now', 'after:performedPeriod.start'],
            'category.coding.*.code' => [
                'required',
                'string',
                new InDictionary('eHealth/procedure_categories')
            ],
            'division.identifier.value' => ['nullable', 'uuid'],
            'outcome.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/procedure_outcomes')
            ],
            'reportOrigin.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/report_origins')
            ]
        ];
    }

    /**
     * Validate form.
     *
     * @param  array  $formData
     * @return void
     * @throws ValidationException
     */
    public function validateForm(array $formData): void
    {
        $validator = Validator::make($formData, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
