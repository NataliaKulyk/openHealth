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
        $this->baseMount();

        $this->personId = $personRequest->id;
        $this->isIncapacitated = PersonRequest::whereId($this->personId)->whereHas('confidantPersons')->exists();

        if ($this->isIncapacitated) {
            $person = $personRequest->confidantPersons->first()->person->toArray();

            // Change id to uuid
            $person['id'] = $person['uuid'];
            unset($person['uuid']);

            $this->selectedConfidantPersonId = $person['id'];
            $this->confidantPerson = [$person];
        }

        $this->form->person = Arr::toCamelCase(
            $personRequest->load([
                'addresses',
                'documents',
                'phones',
                'authenticationMethods',
                'confidantPersons.documentsRelationship'
            ])->toArray()
        );

        $this->address = $this->form->person['addresses'][0];

        if (empty($this->form->person['phones'])) {
            $this->form->person['phones'] = [['type' => null, 'number' => null]];
        }

        if (empty($this->form->person['authenticationMethods'])) {
            $this->form->person['authenticationMethods'] = [['type' => null]];
        }

        if ($this->form->person['confidantPerson']) {
            $this->form->person['confidantPerson']['personId'] = $personRequest->confidantPersons->first()->person->uuid;
        } else {
            $this->form->person['confidantPerson']['documentsRelationship'] = [];
        }
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
