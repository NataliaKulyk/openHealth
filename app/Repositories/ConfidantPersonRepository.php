<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use App\Enums\JobStatus;
use App\Models\Person\Person;
use App\Models\Relations\Phone;
use App\Models\Relations\Document;
use App\Models\Relations\ConfidantPerson;

class ConfidantPersonRepository
{
    public function addConfidantPerson(array $data): void
    {
        $personId = Arr::pull($data, 'person_id');

        $personsData = [];

        foreach ($data as $key => $value) {
            $personsData[] = ['person' => $value];
        }

        foreach ($personsData as $data) {
            $personData = $data['person'];

            // $preferredWayCommunication = Arr::pull($personData, 'preferred_way_communication', null);
            $documentsPerson = Arr::pull($personData, 'documents_person', null);
            $documentsRelationship = Arr::pull($personData, 'documents_relationship', null);
            $phones = Arr::pull($personData, 'phones', []);
            // $relationType = Arr::pull($personData, 'relation_type', null);

            unset($personData['relation_type']);
            unset($personData['preferred_way_communication']);

            $query = Person::where('first_name', $personData['first_name'])
                ->where('last_name', $personData['last_name'])
                ->where('birth_date', $personData['birth_date']);

            if (!empty($personData['tax_id'])) {
                $query->where('tax_id', $personData['tax_id']);
            }

            $person = $query->first();

            if (empty($person)) {
                $person = Person::forceCreate($personData);

                Repository::declarationRequest()->syncRelatedData($person, 'documents', $documentsPerson, Document::class);

                if (!empty($phones)) {
                    Repository::declarationRequest()->syncRelatedData($person, 'phones', $phones, Phone::class);
                }

            }

            $confidantPerson = ConfidantPerson::updateOrCreate(
                ['person_id' => $person->id],
                [
                    'subject_person_id' => $personId,
                    'sync_status' => JobStatus::PARTIAL->value
                ]
            );

            if (!empty($documentsRelationship)) {
                Repository::declarationRequest()->syncRelatedData($confidantPerson, 'documentsRelationship', $documentsRelationship, Document::class);
            }
        }
    }
}
