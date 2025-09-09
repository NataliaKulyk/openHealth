<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Core\Arr;
use App\Models\LegalEntity;
use App\Models\Person\PersonRequest;
use Illuminate\View\View;

class PatientEdit extends PatientComponent
{
    public function mount(LegalEntity $legalEntity, ?int $id = null): void
    {
        $fromDatabase = PersonRequest::find($id, ['id']);

        // Make sure the ID in the URL matches the patient's ID.
        if ($fromDatabase?->id !== $id) {
            abort(404);
        }

        $this->patientId = $id;

        $this->isIncapacitated = PersonRequest::where('id', $this->patientId)
            ->whereHas('confidantPerson')
            ->exists();

        $this->baseMount();

        $this->getPatient();
    }

    /**
     * Get all data about the patient from the DB.
     *
     * @return void
     */
    protected function getPatient(): void
    {
        $patientData = PersonRequest::showPersonRequest($this->patientId)->first();

        // Format data
        $result = [
            'patient' => array_merge($patientData->toArray(), [
                'phones' => count($patientData->phones) === 0
                    ? [['type' => null, 'number' => null]]
                    : $patientData->phones->toArray(),
                'authentication_methods' => $patientData->authenticationMethods->toArray()
            ]),
            'documents' => $patientData->documents->toArray(),
            'address' => $patientData->addresses->toArray(),
            'confidantPerson' => $patientData->confidantPerson?->toArray() ?? []
        ];

        $result = Arr::toCamelCase($result);
        $this->form->fill($result);
        $this->address = $result['address'][0];
        $this->confidantPerson = !empty($result['confidantPerson'])
            ? [$result['confidantPerson']]
            : [];
        $this->selectedConfidantPatientId = $result['confidantPerson']['personUuid'] ?? null;
        $this->form->documentsRelationship = $result['confidantPerson']['documentsRelationship'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.patient.patient-edit');
    }
}
