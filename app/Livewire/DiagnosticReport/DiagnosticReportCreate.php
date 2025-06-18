<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport;

use App\Classes\eHealth\Api\PatientApi;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Repositories\MedicalEvents\Repository;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class DiagnosticReportCreate extends DiagnosticReportComponent
{
    public function render(): View
    {
        return view('livewire.diagnostic-report.create');
    }

    /**
     * Validate and save data.
     *
     * @param  array  $data
     * @return void
     */
    public function save(array $data): void
    {
        $formattedData = $this->prepareFormattedData($data);

        if (!$this->validateFormatted($formattedData)) {
            return;
        }

        try {
            $this->storeValidatedData($formattedData);
        } catch (Exception|Throwable) {
            $this->flashGeneralError();
            Log::channel('db_errors')->error('Error while saving diagnostic report');

            return;
        }
    }

    /**
     * Submit encrypted data.
     *
     * @param  array  $data
     * @return void
     * @throws ApiException
     */
    public function sign(array $data): void
    {
        $formattedData = $this->prepareFormattedData($data);

        if (!$this->validateFormatted($formattedData)) {
            return;
        }

        try {
            $this->storeValidatedData($formattedData);
        } catch (Exception|Throwable) {
            $this->flashGeneralError();
            Log::channel('db_errors')->error('Error while signing diagnostic report');

            return;
        }

        $base64EncryptedData = $this->sendEncryptedData(
            $this->convertArrayKeysToSnakeCase($formattedData),
            Auth::user()->tax_id
        );

        PatientApi::submitDiagnosticReportPackage($this->patientUuid, ['signed_data' => $base64EncryptedData]);

        to_route('patient.index')->with('flashMessage', [
            'message' => 'Діагностичний звіт успішно створений',
            'type' => 'success'
        ]);
    }

    /**
     * Prepare formatted data.
     *
     * @param  array  $data
     * @return array
     */
    protected function prepareFormattedData(array $data): array
    {
        $this->form->diagnosticReports = $this->pruneReferralData($data);

        $diagnosticReport = Repository::diagnosticReport()->formatEHealthRequest($this->form->diagnosticReports);
        $observations = Repository::observation()->formatEHealthRequest(
            $this->form->observations,
            $diagnosticReport['diagnosticReport']['id']
        );

        return array_merge($diagnosticReport, $observations);
    }

    /**
     * Unset unnecessary data based on referral type.
     *
     * @param  array  $data
     * @return array
     */
    protected function pruneReferralData(array $data): array
    {
        if ($data['referralType'] === 'electronic' || $data['referralType'] === '') {
            unset($data['paperReferral']);
        }

        if ($data['referralType'] === 'paper' || $data['referralType'] === '') {
            unset($data['basedOn']);
        }

        return $data;
    }

    /**
     * Validate formatted data.
     *
     * @param  array  $formattedData
     * @return bool
     */
    protected function validateFormatted(array $formattedData): bool
    {
        try {
            $this->form->validateForm('diagnosticReport', $formattedData);

            if (isset($formattedData['observations'])) {
                foreach ($formattedData['observations'] as $observation) {
                    $this->form->validateForm('observations', ['observations' => $observation]);
                }
            }

            return true;
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return false;
        }
    }

    /**
     * Store validated formatted data into DB.
     *
     * @param  array  $formattedData
     * @return void
     * @throws Throwable
     */
    protected function storeValidatedData(array $formattedData): void
    {
        DB::transaction(static function () use ($formattedData) {
            $diagnosticReportId = Repository::diagnosticReport()->store([$formattedData['diagnosticReport']]);

            if (isset($formattedData['observations'])) {
                Repository::observation()->store($formattedData['observations'], diagnosticReportId: $diagnosticReportId);
            }
        });
    }
}
