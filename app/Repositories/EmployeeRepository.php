<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use Exception;
use App\Models\User;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\Employee\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Employee\BaseEmployee;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Throwable;

readonly class EmployeeRepository
{
    public function __construct(
        private UserRepository     $userRepository,
        private PartyRepository    $partyRepository,
        private RevisionRepository $revisionRepository
    ) {

    }

    /**
     * Creates or updates an employee-related model based on UUID.
     * This method is designed to be called ONLY when $employeeModel is NOT null.
     *
     * @param array                    $data          Data for the model.
     * @param Employee|EmployeeRequest $employeeModel The model class to update/create.
     * @param LegalEntity              $legalEntity   The legal entity to associate with.
     *
     * @return BaseEmployee The created or updated model.
     * @throws InvalidArgumentException If $employeeModel is null (should not happen with correct usage).
     */
    public function createOrUpdate(array $data, Employee|EmployeeRequest $employeeModel, LegalEntity $legalEntity): BaseEmployee
    {

        $employee = $employeeModel->updateOrCreate(
            [
                'uuid' => $data['uuid'] ?? '',
            ],
            $data
        );
        $employee->legalEntity()->associate($legalEntity);

        return $employee;
    }

    /**
     * Saves or updates employee-related data, including EmployeeRequest, Party, and associated details.
     *
     * @param array                         $response
     * @param LegalEntity                   $legalEntity
     * @param Employee|EmployeeRequest|null $employeeModel The model class to create/update (can be null for a new request).
     * @param string|null                   $employeeUUID  UUID of an existing Employee, if this is an EmployeeRequest that updates.
     *
     * @return BaseEmployee
     * @throws Exception
     */
    public function store(
        array $response,
        LegalEntity $legalEntity,
        Employee|EmployeeRequest|null $employeeModel,
        ?string $employeeUUID = null
    ): BaseEmployee {
        try {
            $partyData = $response['party'] ?? [];
            $doctorData = $response['doctor'] ?? [];

            $user = null;
            if (!empty($partyData['email'])) {
                $user = $this->userRepository->createIfNotExist($partyData, $response['employee_type']);
            }

            unset($response['party'], $response['doctor'], $response['updated_at']);

            $employee = $this->createOrUpdate($response, $employeeModel, $legalEntity);
            $isEmployeeRequest = $employee instanceof EmployeeRequest;
            $employeeInstance = Employee::where('uuid', $employeeUUID)?->first();
            $alreadyExistParty = $employeeInstance?->party;
            $party = $alreadyExistParty ?? $this->partyRepository->createOrUpdate($partyData);

            if ($isEmployeeRequest) {
                optional($employeeInstance, fn ($instance) => $employee->employee()->associate($instance));
            }

            if (!$isEmployeeRequest || !$alreadyExistParty) {
                $this->updateDetails(
                    $employee,
                    $partyData,
                    $partyData['documents'] ?? [],
                    $partyData['phones'] ?? [],
                    $doctorData['educations'] ?? null,
                    $doctorData['specialities'] ?? null,
                    $doctorData['qualifications'] ?? null,
                    $doctorData['science_degree'] ?? null
                );
            }

            $party->employees()->save($employee);

            if (!$alreadyExistParty && $user) {
                $user->party()->save($party);
            }

            if ($isEmployeeRequest) {
                $responseData = [
                    'response' => $response,
                    'party' => $partyData,
                    'documents' => $partyData['documents'] ?? [],
                    'phones' => $partyData['phones'] ?? [],
                    'doctor' => $doctorData ?? []
                ];

                $this->revisionRepository->saveRevision($employee, [
                    'data' => $responseData,
                    'status' => RevisionStatus::PENDING,
                ]);
            }

            return $employee;

        } catch (Exception $err) {
            Log::error('Create Employee Error: ' . $err->getMessage(), ['exception' => $err]);
            throw new Exception(__('Create Employee Error') . ' : ' . $err->getMessage());
        }
    }

    /**
     * Creates a new EmployeeRequest draft from prepared data.
     * This is a universal method that only handles database persistence.
     *
     * @param array       $employeeRequestData The prepared data for the request itself.
     * @param LegalEntity $legalEntity         The associated LegalEntity model.
     *
     * @return EmployeeRequest
     */
    public function createEmployeeRequestDraft(array $employeeRequestData, LegalEntity $legalEntity): EmployeeRequest
    {
        $employeeRequest = new EmployeeRequest();
        $employeeRequest->fill($employeeRequestData);
        $employeeRequest->status = RequestStatus::NEW;
        $employeeRequest->legalEntity()->associate($legalEntity);
        $employeeRequest->save();

        return $employeeRequest;
    }

    /**
     * Finds all pending employee requests for a given user and legal entity.
     * This encapsulates the database query, keeping the service layer clean.
     */
    public function findPendingRequestsForUser(User $user, LegalEntity $legalEntity): Collection
    {
        $user->loadMissing('party');

        return EmployeeRequest::query()
            ->with(['revision', 'party'])
            ->where('legal_entity_id', $legalEntity->id)
            ->whereIn('status', RequestStatus::getStatusesForSync())
            ->whereNotNull('uuid')
            ->where('party_id', $user->party->id)
            ->get();
    }

    /**
     * @param Employee|EmployeeRequest $employee the model or identifier (ID or UUID) of the employee to update
     * @param array                    $party
     * @param array                    $documents
     * @param array                    $phones
     * @param array|null               $educations
     * @param array|null               $specialities
     * @param array|null               $qualifications
     * @param array|null               $scienceDegree
     *
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
     * The logic behind the party update or create is as follows:
     * 1. Check party by UUID. Possible scenario: the party already exists in the system
     * 2. If user already has a party, update it.
     * 3. If user does not have a party, but there is a party with the same UUID, update it and establish the relation.
     * 4. If neither of the above, create a new party and establish the relation.
     */
    protected function updatePartyByUuid(Employee|EmployeeRequest $model, array $party): void
    {
        $partyUuid = Arr::get($party, 'uuid');
        $partyByUuid = Party::where('uuid', $partyUuid)->first();

        // If the model doesn't have a party and party doesn't exist, create new one. It's a brand-new person
        if (!$partyByUuid && !$model->party) {
            $newParty = new Party($party);
            $newParty->save();
            $model->party()->associate($newParty)->save();

            // If the model doesn't have a related party but the party already exists, update it and relate - the scenario of a new employee with already created person/party
        } elseif ($partyByUuid && !$model->party) {
            $model->party()->associate($partyByUuid)->save();

            // The model already has a related party, update it and change the UUID - the case when eHealth creates another party, probably merge scenario
        } elseif (!$partyByUuid && $model->party) {
            $model->party()->update($party);
            // Both the model and the party exist, check if they are the same
        } elseif ($partyByUuid && $model->party) {

            // uuid is the same, just update
            if ($partyByUuid->uuid === $model->party->uuid) {
                $model->party()->update($party);
            } else {

                // Different uuid, need to merge the results, prioritizing the eHealth data
                $result = array_merge(
                    $model->party()->toArray(),
                    $partyByUuid->toArray()
                );

                $model->party()->update($result);

            }
        }
    }
}
