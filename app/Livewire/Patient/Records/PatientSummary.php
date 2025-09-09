<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Records;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;

class PatientSummary extends BasePatientComponent
{
    public array $episodes;

    public array $diagnoses;

    public array $observations;

    /**
     * Get patient episodes.
     *
     * @return void
     */
    public function getEpisodes(): void
    {
        try {
            $response = EHealth::person()->getShortEpisodes($this->uuid);

            $this->episodes = $response->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting short episodes');
            session()?->flash('error', 'Не вдалося отримати епізоди. Спробуйте пізніше.');

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting short episodes');
            session()?->flash('error', 'Не вдалося отримати епізоди. Спробуйте пізніше.');

            return;
        }
    }

    /**
     * Get patient diagnoses.
     *
     * @return void
     */
    public function getDiagnoses(): void
    {
        try {
            $response = EHealth::person()->getActiveDiagnoses($this->uuid);

            $this->diagnoses = $response->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting active diagnoses');
            session()?->flash('error', 'Не вдалося отримати діагнози. Спробуйте пізніше.');

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting active diagnoses');
            session()?->flash('error', 'Не вдалося отримати діагнози. Спробуйте пізніше.');

            return;
        }
    }

    /**
     * Get patient observations.
     *
     * @return void
     */
    public function getObservations(): void
    {
        try {
            $response = EHealth::person()->getObservations($this->uuid);

            $this->observations = $response->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting observations');
            session()?->flash('error', 'Не вдалося отримати обстеження. Спробуйте пізніше.');

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting observations');
            session()?->flash('error', 'Не вдалося отримати обстеження. Спробуйте пізніше.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.patient.records.patient-summary');
    }
}
