<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use App\Enums\Status;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Enums\Employee\RequestStatus;
use App\Models\Employee\EmployeeRequest;
use Log;
use Throwable;

readonly class EmployeeRepository
{
    /**
     * Creates a new EmployeeRequest draft from prepared data.
     * This is a universal method that only handles database persistence.
     *
     * @param  array  $employeeRequestData  The prepared data for the request itself.
     * @param  LegalEntity  $legalEntity  The associated LegalEntity model.
     * @param  Employee|null  $employee  (Optional) The existing employee being edited.
     * @return EmployeeRequest
     */
    public function createEmployeeRequestDraft(array $employeeRequestData, LegalEntity $legalEntity, ?Employee $employee = null): EmployeeRequest
    {
        $employeeRequest = new EmployeeRequest();
        $employeeRequest->fill($employeeRequestData);
        $employeeRequest->status = RequestStatus::NEW;
        $employeeRequest->legalEntity()->associate($legalEntity);

        if ($employee) {
            $employeeRequest->employee()->associate($employee);
        }

        $employeeRequest->save();

        return $employeeRequest;
    }

    /**
     * @param  Employee|EmployeeRequest  $employee  the model or identifier (ID or UUID) of the employee to update
     * @param  array  $party
     * @param  array  $documents
     * @param  array  $phones
     * @param  array|null  $educations
     * @param  array|null  $specialities
     * @param  array|null  $qualifications
     * @param  array|null  $scienceDegree
     * @return Employee|EmployeeRequest Updated employee
     * @throws Throwable
     */
    public function updateDetails(
        Employee|EmployeeRequest $employee,
        array $party,
        array $documents,
        array $phones,
        ?array $educations = null,
        ?array $specialities = null,
        ?array $qualifications = null,
        ?array $scienceDegree = null,
    ): Employee|EmployeeRequest {
        $model = $employee;

        DB::transaction(function () use ($model, $party, $documents, $phones, $educations, $specialities, $qualifications, $scienceDegree) {
            $partyAttributes = array_diff_key($party, array_flip(['documents', 'phones']));

            $this->updatePartyByUuid($model, $partyAttributes);

            $model->party->syncMany('documents', $documents);
            $model->party->syncMany('phones', $phones);
            $model->syncMany('educations', $educations);
            $model->syncMany('specialities', $specialities);
            $model->syncMany('qualifications', $qualifications);

            if (!empty($scienceDegree)) {
                $model->scienceDegree()->updateOrCreate([], $scienceDegree);
            } else {
                $model->scienceDegree()->delete();
            }
        });

        return $model;
    }

    /**
     * Returns a Party Query Builder sorted by the latest activity date.
     * Activity is defined as the most recent 'updated_at' timestamp
     * from either related Employee records or EmployeeRequest records.
     *
     * We use raw SQL subqueries in ORDER BY because standard SQL (Postgres)
     * does not allow using SELECT aliases inside complex ORDER BY functions (COALESCE/GREATEST).
     *
     * @param int $legalEntityId
     * @return Builder
     */
    public function getPartiesWithLatestActivityQuery(int $legalEntityId): Builder
    {
        // 1. Prepare safe integer ID for raw SQL insertion
        $leId = (int) $legalEntityId;

        // 2. Define Raw SQL for the latest Employee modification
        $latestEmployeeSql = "
            SELECT updated_at
            FROM employees
            WHERE party_id = parties.id
            AND legal_entity_id = {$leId}
            ORDER BY updated_at DESC
            LIMIT 1
        ";

        // 3. Define Raw SQL for the latest EmployeeRequest modification
        $latestRequestSql = "
            SELECT updated_at
            FROM employee_requests
            WHERE party_id = parties.id
            AND legal_entity_id = {$leId}
            ORDER BY updated_at DESC
            LIMIT 1
        ";

        // 4. Construct the main query
        return Party::query()
            ->select('parties.*')
            // Inject subqueries as virtual columns for debugging/view usage
            ->selectRaw("($latestEmployeeSql) as last_employee_activity")
            ->selectRaw("($latestRequestSql) as last_request_activity")
            ->with([
                'phones',
                'users',
                // Sort children: recently updated employees first
                'employees' => fn ($q) => $q
                    ->where('legal_entity_id', $legalEntityId)
                    ->orderByDesc('updated_at')
                    ->with(['division', 'user']),
                // Sort children: recently updated requests first
                'employeeRequests' => fn ($q) => $q
                    ->where('legal_entity_id', $legalEntityId)
                    ->whereIn('status', [Status::NEW->value, Status::SIGNED->value])
                    ->orderByDesc('updated_at')
                    ->with(['revision', 'division'])
            ])
            // 5. SORTING: We repeat the subqueries inside the GREATEST function.
            // This allows the DB to calculate the value on the fly for sorting purposes.
            ->orderByRaw("GREATEST(COALESCE(($latestEmployeeSql), '1970-01-01'), COALESCE(($latestRequestSql), '1970-01-01')) DESC");
    }

    /**
     * The logic behind the party update or create is as follows:
     * 1. Check party by UUID. Possible scenario: the party already exists in the system
     * 2. If user already has a party, update it.
     * 3. If user does not have a party, but there is a party with the same UUID, update it and establish the relation.
     * 4. If neither of the above, create a new party and establish the relation.
     */
    protected function updatePartyByUuid(Employee|EmployeeRequest $model, array $party): void
    {
        unset($party['email']);
        $partyUuid = Arr::get($party, 'uuid');
        $partyByUuid = Party::where('uuid', $partyUuid)->first();

        // If the model doesn't have a party and party doesn't exist, create new one. It's a brand-new person
        if (!$partyByUuid && !$model->party) {
            $newParty = new Party($party);
            $newParty->save();
            $model->party()->associate($newParty)->save();

            // If the model doesn't have a related party but the party already exists, update it and relate - the scenario of a new employee with already created person/party
        } else if ($partyByUuid && !$model->party) {
            $partyByUuid->update($party);
            $model->party()->associate($partyByUuid)->save();

            // The model already has a related party, update it and change the UUID - the case when eHealth creates another party, probably merge scenario
        } else if (!$partyByUuid && $model->party) {
            $model->party()->update($party);

            // Both the model and the party exist, check if they are the same
        } else if ($partyByUuid && $model->party) {

            // uuid is the same, just update
            if ($partyByUuid->uuid === $model->party->uuid) {
                $model->party()->update($party);
            } else {
                // Different uuid, need to merge the results, prioritizing the eHealth data
                $model->party()->update($party);

                Log::warning('Potential party merge scenario detected', [
                    'model_party_uuid'          => $model->party->uuid,
                    'ehealth_party_uuid'        => $partyByUuid->uuid,
                    'updated_with_ehealth_data' => true
                ]);
            }
        }
    }
}
