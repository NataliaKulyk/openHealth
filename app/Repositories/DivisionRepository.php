<?php

namespace App\Repositories;

use Arr;
use Exception;
use App\Models\Division;
use App\Models\LegalEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DivisionRepository
{
    /**
     * Saves a list of divisions to the database.
     *
     * @param mixed $responseList The list of divisions to be saved
     * @param LegalEntity|null $legalEntity Optional legal entity associated with the divisions
     *
     * @return void
     *
     * @throws \Exception
     */
    public function saveDivisionsList($responseList, ?LegalEntity $legalEntity = null): void
    {
        $legalEntity ??= legalEntity();

        DB::transaction(function () use ($responseList, $legalEntity) {
            foreach ($responseList as $responseItem) {
                $this->saveDivisionData($responseItem, $legalEntity);
            }
        });
    }

    /**
     * Set status for specific action (for activate or deactivate)
     *
     * @param \App\Models\Division $division
     * @param string $status
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setAction(Division $division, string $status): void
    {
        try {
            $division->setAttribute('status', $status)->save();

            $division->refresh();

        } catch (Exception $err) {
            throw new Exception($err->getMessage());
        }
    }

    /**
     * Create instance of Division cclass
     *
     * @param array $responseData // The data array suitable to do fill on Division Model
     *
     * @return Division|null
     */
    public function createOrUpdate(array $responseData): Division|null
    {
        $uuid= $responseData['uuid'];
        $legalEntityUuid = $responseData['legal_entity_uuid'];

        Arr::forget($responseData, [
            'legal_entity_uuid',
            'addresses',
            'phones',
        ]);

        $division = Division::firstOrNew(['uuid' => $uuid]);

        $division->fill($responseData);

        $division->setAttribute('legal_entity_uuid', $legalEntityUuid); // Here legal_entity_id is UUID by eHealths notation

        $division->setAttribute('external_id', $responseData['external_id']);
        $division->setAttribute('status', $responseData['status']);

        return $division;
    }


    /**
     * Saves raw division form data to the database.
     *
     * @param array $divisionData The raw form data containing division information
     *
     * @return void
     *
     * @throws \Exception If there is an error saving the data
     */
    public function saveRawFormData(array $divisionData): void
    {
        // TODO: in the next PR's realize this functionality (store data from the division's form to the DB)

        return;
    }

    /**
     * Create instance of Division model and save it's data to the DB (with all it's relations aka: Address, Phone and LegalEntity)
     *
     * @param array $divisionData
     * @param \App\Models\LegalEntity $legalEntity
     *
     * @return Division
     */
    public function saveDivisionData(array $divisionData, LegalEntity $legalEntity): Division
    {
        $division = $this->createOrUpdate($divisionData);

        $division = $this->createLegalEntityRelation($division, $legalEntity);

        $division->save();

        $division->refresh();

        Repository::address()->syncAddresses($division, $divisionData['addresses']);

        Repository::phone()->syncPhones($division, $divisionData['phones']);

        return $division;
    }

    /**
     * TODO: need more testing on further PRs
     * Create instance of Division model and save it's data to the DB (with all it's relations aka: Address, Phone and LegalEntity)
     *
     * @param array $divisionData
     * @param \App\Models\LegalEntity $legalEntity
     *
     * @return Division
     */
    public function syncDivisionData(array $divisionData, LegalEntity $legalEntity): Division
    {
        $division = $this->createOrUpdate($divisionData);

        if ($division->update()) {
            $division->refresh();
        }

        Repository::address()->syncAddresses($division, $divisionData['addresses']);

        Repository::phone()->syncPhones($division, $divisionData['phones']);

        return $division;
    }

    /**
     * Creates a relation between a Division and a LegalEntity
     *
     * @param Division $division The division to create the relation for
     * @param LegalEntity $legalEntity The legal entity to relate to the division
     *
     * @return Division Returns the updated Division instance
     */
    public function createLegalEntityRelation(Division $division, LegalEntity $legalEntity): Division
    {
        return $division->legalEntity()->associate($legalEntity);
    }

    /**
     * Creates a relation between a Division and such models as Employee, EmployeeRequest etc.
     *
     * @param Division $division The division entity to create relation for
     * @param Object $model The model to create relation with
     *
     * @return void
     */
    public function createRelationForDivision(Division $division, Object $model)
    {
        if (! $model) {
            return;
        }

        if ($model?->division_id === $division->id) {
            return;
        }

        $model->division()->associate($division);

        if (Schema::hasColumn($model->getTable(), 'division_uuid')) {
            $model->division_uuid = $division->uuid;
        }

        $model->save();
    }
}
