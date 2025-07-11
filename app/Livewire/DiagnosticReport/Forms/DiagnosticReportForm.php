<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport\Forms;

use App\Rules\InDictionary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class DiagnosticReportForm extends Form
{
    public array $diagnosticReports;

    public array $observations;

    protected function rules(): array
    {
        return [
            'diagnosticReport.category.*.coding.*.code' => [
                'required',
                'string',
                new InDictionary('eHealth/diagnostic_report_categories')
            ],
            'diagnosticReport.code.identifier.value' => ['required'],
            'diagnosticReport.paperReferral.requisition' => ['nullable', 'string', 'max:255'],
            'diagnosticReport.paperReferral.requesterEmployeeName' => ['nullable', 'string', 'max:255'],
            'diagnosticReport.paperReferral.requesterLegalEntityEdrpou' => [
                Rule::requiredIf(data_get($this->diagnosticReports, 'referralType') === 'paper'),
                'regex:/^[0-9]{8,10}$/',
                'string',
                'max:255'
            ],
            'diagnosticReport.paperReferral.requesterLegalEntityName' => [
                Rule::requiredIf(data_get($this->diagnosticReports, 'referralType') === 'paper'),
                'string',
                'max:255'
            ],
            'diagnosticReport.paperReferral.serviceRequestDate' => [
                Rule::requiredIf(data_get($this->diagnosticReports, 'referralType') === 'paper'),
                'date'
            ],
            'diagnosticReport.paperReferral.note' => ['nullable', 'string', 'max:255'],
            'diagnosticReport.effectiveDateTime' => [
                'nullable',
                'date',
                'required_without_all:diagnosticReport.effectivePeriod.start,diagnosticReport.effectivePeriod.end'
            ],
            'diagnosticReport.effectivePeriod.start' => [
                'nullable',
                'date',
                'before_or_equal:now',
                'required_without:diagnosticReport.effectiveDateTime'
            ],
            'diagnosticReport.effectivePeriod.end' => [
                'nullable',
                'date',
                'after:diagnosticReport.effectivePeriod.start',
                'required_without:diagnosticReport.effectiveDateTime'
            ],
            'diagnosticReport.issued' => ['required', 'date', 'before_or_equal:now'],
            'diagnosticReport.conclusionCode.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/ICD10_AM/condition_codes')
            ],
            'diagnosticReport.conclusion' => ['nullable', 'string'],
            'diagnosticReport.resultsInterpreter.text' => ['required', 'string', 'max:255'],

            'observations.primarySource' => ['required', 'boolean'],
            'observations.performer' => [
                'required_if:observations.primarySource,true',
                'prohibited_if:observations.primarySource,false',
                'array'
            ],
            'observations.reportOrigin' => [
                'required_if:observations.primarySource,false',
                'prohibited_if:observations.primarySource,true',
                'array'
            ],
            'observations.categories' => ['required', 'array'],
            'observations.categories.coding.*.code' => [
                'required',
                'string',
                new InDictionary(['eHealth/observation_categories', 'eHealth/ICF/observation_categories'])
            ],
            'observations.code' => ['required', 'array'],
            'observations.code.coding.*.code' => [
                'required',
                'string',
                new InDictionary(['eHealth/LOINC/observation_codes', 'eHealth/ICF/classifiers'])
            ],
            'observations.valueQuantity' => ['sometimes', 'array'],
            'observations.valueQuantity.value' => ['sometimes', 'numeric'],
            'observations.valueQuantity.comparator' => ['sometimes', 'string'],
            'observations.valueQuantity.unit' => ['sometimes', 'string'],
            'observations.valueQuantity.system' => ['sometimes', 'string'],
            'observations.valueQuantity.code' => ['sometimes', 'string'],
            'observations.valueCodeableConcept' => ['sometimes', 'array'],
            'observations.valueString' => ['sometimes', 'string'],
            'observations.valueBoolean' => ['sometimes', 'boolean'],
            'observations.valueDateTime' => ['sometimes', 'date'],
            'observations.method.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/observation_methods')
            ],
            'observations.interpretation.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary('eHealth/observation_interpretations')
            ],
            'observations.issued' => ['required', 'date', 'before_or_equal:now'],
            'observations.effectiveDateTime' => ['nullable', 'date', 'before_or_equal:now']
        ];
    }

    /**
     * Validate form by name.
     *
     * @param  string  $formName
     * @param  array  $formData
     * @return void
     * @throws ValidationException
     */
    public function validateForm(string $formName, array $formData): void
    {
        $rules = $this->rulesForModel($formName)->toArray();

        $validator = Validator::make($formData, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
