<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport\Forms;

use App\Rules\InDictionary;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

class DiagnosticReportForm extends Form
{
    public array $diagnosticReports;

    protected function rules(): array
    {
        $requiredOrSometimes = $this->diagnosticReports['referralType'] === 'paper' ? 'required' : 'sometimes';

        return [
            'diagnosticReport.category.*.coding.*.code' => [
                'required',
                'string',
                new InDictionary('eHealth/diagnostic_report_categories')
            ],
            'diagnosticReport.code.identifier.value' => ['required'],
            "diagnosticReport.paperReferral.requisition" => ['nullable', 'string', 'max:255'],
            "diagnosticReport.paperReferral.requesterEmployeeName" => ['nullable', 'string', 'max:255'],
            "diagnosticReport.paperReferral.requesterLegalEntityEdrpou" => [
                $requiredOrSometimes,
                'regex:/^[0-9]{8,10}$/',
                'string',
                'max:255'
            ],
            "diagnosticReport.paperReferral.requesterLegalEntityName" => [$requiredOrSometimes, 'string', 'max:255'],
            "diagnosticReport.paperReferral.serviceRequestDate" => [$requiredOrSometimes, 'date'],
            "diagnosticReport.paperReferral.note" => ['nullable', 'string', 'max:255'],
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
