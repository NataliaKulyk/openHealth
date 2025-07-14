<?php

declare(strict_types=1);

namespace App\Livewire\Encounter;

use App\Classes\eHealth\Api\PatientApi;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Livewire\Encounter\Forms\Api\EncounterRequestApi;
use App\Models\LegalEntity;
use App\Repositories\MedicalEvents\Repository;
use App\Traits\HandlesReasonReferences;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EncounterCreate extends EncounterComponent
{
    use HandlesReasonReferences;

    public function mount(LegalEntity $legalEntity, int $patientId): void
    {
        $this->initializeComponent($patientId);
        $this->setEmployeePartyData();
        $this->setDefaultDate();
    }

    /**
     * Validate and save data.
     *
     * @return void
     * @throws Throwable
     */
    public function save(): void
    {
        $formattedData = $this->prepareFormattedData();

        $this->validateFormatted($formattedData);
        $this->storeValidatedData($formattedData);
    }

    /**
     * Submit encrypted data about person encounter.
     *
     * @return void
     * @throws Throwable
     */
    public function sign(): void
    {
        $formattedData = $this->prepareFormattedData();

        $this->validateFormatted($formattedData);
        $this->storeValidatedData($formattedData);

        if ($this->episodeType === 'new') {
            $this->createEpisode($formattedData['episode']);
        }

        $base64EncryptedData = $this->sendEncryptedData(
            $this->convertArrayKeysToSnakeCase($formattedData),
            Auth::user()->tax_id
        );

        $signedSubmitEncounter = EncounterRequestApi::buildSubmitEncounterPackage(
            $formattedData['encounter'],
            $base64EncryptedData
        );
        PatientApi::submitEncounter($this->patientUuid, $signedSubmitEncounter);
    }

    /**
     * Set required employee party data.
     *
     * @return void
     */
    protected function setEmployeePartyData(): void
    {
        $employeeUuid = $this->authEmployee->uuid;

        $this->form->encounter['performer']['identifier']['value'] = $employeeUuid;
        $this->form->episode['careManager']['identifier']['value'] = $employeeUuid;
    }

    /**
     * Set default encounter period date.
     *
     * @return void
     */
    private function setDefaultDate(): void
    {
        $now = CarbonImmutable::now();

        $this->form->encounter['period'] = [
            'start' => $now->format('H:i'),
            'end' => $now->addMinutes(15)->format('H:i')
        ];
    }

    /**
     * Prepare formatted data.
     *
     * @return array
     */
    protected function prepareFormattedData(): array
    {
        $encounterRepository = Repository::encounter();

        $data = [
            'encounter' => $encounterRepository->formatEncounterRequest(
                $this->form->encounter,
                $this->form->conditions,
                $this->episodeType === 'new'
            ),
            'episode' => $this->episodeType === 'new'
                ? $encounterRepository->formatEpisodeRequest($this->form->episode, $this->form->encounter['period'])
                : [],
            'conditions' => $encounterRepository->formatConditionsRequest($this->form->conditions),
            'immunizations' => !empty($this->form->immunizations)
                ? $encounterRepository->formatImmunizationsRequest($this->form->immunizations)
                : [],
            'diagnosticReports' => !empty($this->form->diagnosticReports)
                ? $encounterRepository->formatDiagnosticReportsRequest(
                    $this->form->diagnosticReports,
                    $this->form->encounter['division']['identifier']['value']
                )
                : [],
            'observations' => !empty($this->form->observations)
                ? $encounterRepository->formatObservationsRequest($this->form->observations)
                : [],
            'procedures' => !empty($this->form->procedures)
                ? $encounterRepository->formatProceduresRequest($this->form->procedures)
                : []
        ];

        // Remove empty
        return array_filter($data);
    }

    /**
     * Validate formatted data.
     *
     * @param  array  $formattedData
     * @return void
     */
    protected function validateFormatted(array $formattedData): void
    {
        try {
            $this->form->validateForm('encounter', $formattedData['encounter']);

            if (isset($formattedData['episode'])) {
                $this->form->validateForm('episode', $formattedData['episode']);
            }

            foreach ($formattedData['conditions'] as $formattedCondition) {
                $this->form->validateForm('conditions', $formattedCondition);
            }

            if (isset($formattedData['immunizations'])) {
                foreach ($formattedData['immunizations'] as $formattedImmunization) {
                    $this->form->validateForm('immunizations', $formattedImmunization);
                }
            }

            if (isset($formattedData['diagnosticReports'])) {
                foreach ($formattedData['diagnosticReports'] as $formattedDiagnosticReport) {
                    $this->form->validateForm('diagnosticReports', $formattedDiagnosticReport);
                }
            }

            if (isset($formattedData['observations'])) {
                foreach ($formattedData['observations'] as $formattedObservation) {
                    $this->form->validateForm('observations', $formattedObservation);
                }
            }

            if (isset($formattedData['procedures'])) {
                foreach ($formattedData['procedures'] as $formattedProcedure) {
                    $this->form->validateForm('procedures', $formattedProcedure);
                }
            }
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
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
        DB::transaction(function () use ($formattedData) {
            $createdEncounterId = Repository::encounter()->store($formattedData['encounter'], $this->patientId);

            if (isset($formattedData['episode'])) {
                Repository::episode()->store($formattedData['episode'], $createdEncounterId);
            }

            Repository::condition()->store($formattedData['conditions'], $createdEncounterId);

            if (isset($formattedData['immunizations'])) {
                Repository::immunization()->store($formattedData['immunizations'], $createdEncounterId);
            }

            if (isset($formattedData['diagnosticReports'])) {
                Repository::diagnosticReport()->store($formattedData['diagnosticReports'], $createdEncounterId);
            }

            if (isset($formattedData['observations'])) {
                Repository::observation()->store($formattedData['observations'], $createdEncounterId);
            }

            if (isset($formattedData['procedures'])) {
                Repository::procedure()->store($formattedData['procedures'], $createdEncounterId);

                // Save the selected condition and observation locally if they don't exist in our database.
                foreach ($formattedData['procedures'] as $procedure) {
                    $this->processReasonReferences($procedure);
                    $this->processComplicationDetails($procedure);
                }
            }
        });
    }

    /**
     * Create episode for patient.
     *
     * @param  array  $formattedEpisode
     * @return void
     */
    protected function createEpisode(array $formattedEpisode): void
    {
        try {
            PatientApi::createEpisode($this->patientUuid, $this->convertArrayKeysToSnakeCase($formattedEpisode));
        } catch (ApiException) {
            $this->dispatch('flashMessage', [
                'message' => __('Виникла помилка при створенні епізоду. Зверніться до адміністратора.'),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Handles details of procedure complications
     *
     * @param  array  $procedure
     * @return void
     */
    private function processComplicationDetails(array $procedure): void
    {
        if (!isset($procedure['complicationDetails'])) {
            return;
        }

        foreach ($procedure['complicationDetails'] as $complicationDetail) {
            $this->ensureConditionExists($complicationDetail['identifier']['value']);
        }
    }
}
