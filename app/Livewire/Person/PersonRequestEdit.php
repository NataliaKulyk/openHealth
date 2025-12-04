<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Core\Arr;
use App\Models\LegalEntity;
use App\Models\Person\PersonRequest;
use Illuminate\View\View;

/**
 * Used for editing draft
 */
class PersonRequestEdit extends PersonComponent
{
    public function mount(LegalEntity $legalEntity, PersonRequest $personRequest): void
    {
        $this->personId = $personRequest->id;
        $this->isIncapacitated = PersonRequest::whereId($this->personId)->whereHas('confidantPerson')->exists();
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
        $patientData = PersonRequest::showPersonRequest($this->personId)->first();

        // Format data
        $result = [
            'person' => array_merge($patientData->toArray(), [
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
        $this->selectedConfidantPersonId = $result['confidantPerson']['personUuid'] ?? null;
        $this->form->documentsRelationship = $result['confidantPerson']['documentsRelationship'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
