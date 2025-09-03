<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Declaration;
use App\Models\DeclarationRequest;
use App\Models\Division;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Person\Person;

class DeclarationRepository
{
    /**
     * Store data during first request or local saving.
     *
     * @param  array  $validatedData
     * @return Declaration
     */
    public function store(array $validatedData): Declaration
    {
        $validatedData = $this->mapUuidsToIds($validatedData);

        return Declaration::create($validatedData);
    }

    /**
     * Map uuids to ids for setting relationship.
     *
     * @param  array  $data
     * @return array
     */
    private function mapUuidsToIds(array $data): array
    {
        $data['uuid'] = $data['id'];
        unset($data['id']);

        $data['declaration_request_id'] = DeclarationRequest::where('uuid', $data['declaration_request_id'])
            ->pluck('id')
            ->firstOrFail();
        $data['employee_id'] = Employee::withoutEagerLoads()
            ->where('uuid', $data['employee_id'])
            ->pluck('id')
            ->firstOrFail();
        $data['person_id'] = Person::where('uuid', $data['person_id'])->pluck('id')->firstOrFail();
        $data['division_id'] = Division::where('uuid', $data['division_id'])
            ->pluck('id')
            ->firstOrFail();
        $data['legal_entity_id'] = LegalEntity::where('uuid', $data['legal_entity_id'])
            ->pluck('id')
            ->firstOrFail();

        return $data;
    }
}
