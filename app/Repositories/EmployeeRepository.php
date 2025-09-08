<?php

declare(strict_types=1);

namespace App\Repositories;

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

class EmployeeRepository
{
    protected ?UserRepository          $userRepository;
    protected ?PartyRepository         $partyRepository;
    protected ?PhoneRepository         $phoneRepository;
    protected ?DocumentRepository      $documentRepository;
    protected ?EducationRepository     $educationRepository;
    protected ?ScienceDegreeRepository $scienceDegreeRepository;
    protected ?QualificationRepository $qualificationRepository;
    protected ?SpecialityRepository    $specialityRepository;
    protected ?RevisionRepository      $revisionRepository;

    public function __construct(
        UserRepository          $userRepository,
        PartyRepository         $partyRepository,
        PhoneRepository         $phoneRepository,
        DocumentRepository      $documentRepository,
        EducationRepository     $educationRepository,
        ScienceDegreeRepository $scienceDegreeRepository,
        QualificationRepository $qualificationRepository,
        SpecialityRepository    $specialityRepository,
        RevisionRepository      $revisionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->partyRepository = $partyRepository;
        $this->phoneRepository = $phoneRepository;
        $this->documentRepository = $documentRepository;
        $this->educationRepository = $educationRepository;
        $this->scienceDegreeRepository = $scienceDegreeRepository;
        $this->qualificationRepository = $qualificationRepository;
        $this->specialityRepository = $specialityRepository;
        $this->revisionRepository = $revisionRepository;
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
            unset($response['party']);

            if (!empty($partyData['phones'])) {
                $phonesData = $partyData['phones'];
                unset($partyData['phones']);
            }
            if (!empty($partyData['documents'])) {
                $documentsData = $partyData['documents'];
                unset($partyData['documents']);
            }
            if (!empty($response['doctor'])) {
                $doctorData = $response['doctor'];
                unset($response['doctor']);
            }

            unset($response['updated_at']);

            $user = null;

            if (!empty($partyData['email'])) {
                $this->userRepository->createIfNotExist($partyData, $response['employee_type']);
            }

            $employee = $this->createOrUpdate($response, $employeeModel, $legalEntity);
            $isEmployeeRequest = $employee instanceof EmployeeRequest;
            $employeeInstance = Employee::where('uuid', $employeeUUID)?->first();
            $alreadyExistParty = $employeeInstance?->party;
            $party = $alreadyExistParty ?? $this->partyRepository->createOrUpdate($partyData);

            if ($isEmployeeRequest) {
                optional($employeeInstance, fn ($instance) => $employee->employee()->associate($instance));
            }

            /**
             * If $alreadyExistParty == null it only means that EmployeeRequest expects to create through creation of the LegalEntity
             * Because if $employee is EmployeeRequest the data below mustn't be changed until a valid user approves these changes.
             * And therefore, if $employee is Employee, the data should be updated or created.
             */
            if (!$isEmployeeRequest || !$alreadyExistParty) {
                // Add documents for Party
                $this->documentRepository->syncDocuments($party, $documentsData ?? []);

                // Add phones for Party
                $this->phoneRepository->syncPhones($party, $phonesData ?? []);

                // Add educations
                $this->educationRepository->addEducations($employee, $doctorData['educations'] ?? []);

                // Add science degrees
                $this->scienceDegreeRepository->addScienceDegrees($employee, $doctorData['science_degree'] ?? []);

                // Add qualifications
                $this->qualificationRepository->addQualifications($employee, $doctorData['qualifications'] ?? []);

                // Add specialities
                $this->specialityRepository->addSpecialities($employee, $doctorData['specialities'] ?? []);
            }

            $party->employees()->save($employee);

            // Assign party to the user if $user is a new one
            if (!$alreadyExistParty && $user) {
                $user->party()->save($party);
            }

            if ($isEmployeeRequest) {
                $responseData = [
                    'response' => $response,
                    'party' => $partyData,
                    'documents' => $documentsData,
                    'phones' => $phonesData,
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
     * @param array $employeeRequestData The prepared data for the request itself.
     * @param Party $party The associated Party model.
     * @param LegalEntity $legalEntity The associated LegalEntity model.
     * @param User|null $user The associated User model, if found.
     * @return EmployeeRequest
     */
    public function createEmployeeRequestDraft(array $employeeRequestData, Party $party, LegalEntity $legalEntity, ?User $user): EmployeeRequest
    {
        $employeeRequest = new EmployeeRequest();
        $employeeRequest->fill($employeeRequestData);
        $employeeRequest->status = 'NEW';
        $employeeRequest->legalEntity()->associate($legalEntity);
        $employeeRequest->party()->associate($party);

        if ($user) {
            $employeeRequest->user()->associate($user);
        }

        $employeeRequest->save();

        return $employeeRequest;
    }

    /**
     * Finds all pending employee requests for a given user and legal entity.
     * This encapsulates the database query, keeping the service layer clean.
     *
     * @param User $user
     * @param LegalEntity $legalEntity
     * @return Collection
     */
    public function findPendingRequestsForUser(User $user, LegalEntity $legalEntity): Collection
    {
        $user->loadMissing('party');

        return EmployeeRequest::query()
            ->with('revision')
            ->where('legal_entity_id', $legalEntity->id)
            ->whereIn('status', RequestStatus::getStatusesForSync())
            ->whereNotNull('uuid')
            ->where(function ($query) use ($user) {

                $query->where('user_id', $user->id);
            })
            ->get();
    }

    /**
     * Creates or updates multiple Employee records in a single query.
     *
     * @param array $employees
     */
    public function upsertEmployees(array $employees): void
    {
        if (empty($employees)) {
            return;
        }

        // Define the unique key (uuid) and the columns to update if the record exists.
        $updateColumns = array_keys(reset($employees));

        Employee::upsert($employees, ['uuid'], $updateColumns);
    }

    /**
     * Retrieves a map of employee UUIDs to their primary IDs.
     *
     * @param array $uuids
     * @return array ['uuid' => 'id', ...]
     */
    public function getEmployeeIdsByUuids(array $uuids): array
    {
        if (empty($uuids)) {
            return [];
        }

        return Employee::whereIn('uuid', $uuids)->pluck('id', 'uuid')->all();
    }

    /**
     * Updates multiple EmployeeRequest records in a single query using a CASE statement.
     *
     * @param array $requestsData ['request_id' => ['column' => 'value', ...]]
     */
    public function bulkUpdateEmployeeRequests(array $requestsData): void
    {
        if (empty($requestsData)) {
            return;
        }

        $requestIds = array_keys($requestsData);
        $table = new EmployeeRequest()->getTable();

        // Prepare CASE parts for each column that needs to be updated.
        $cases = [];
        $bindings = [];
        $columns = array_keys(reset($requestsData)); // e.g., ['employee_id', 'applied_at', 'status']

        foreach ($columns as $column) {
            // Use double quotes for PostgreSQL compatibility.
            $cases[$column] = "CASE \"id\" ";
            foreach ($requestsData as $id => $data) {
                $cases[$column] .= "WHEN ? THEN ? ";
                $bindings[] = $id;
                $bindings[] = $data[$column];
            }
            $cases[$column] .= "ELSE \"$column\" END";
        }

        // Assemble the SQL query with PostgreSQL-compatible syntax.
        $updateQuery = "UPDATE \"{$table}\" SET ";
        $updateStatements = [];
        foreach ($cases as $column => $case) {
            $updateStatements[] = "\"{$column}\" = {$case}";
        }
        $updateQuery .= implode(', ', $updateStatements);
        $updateQuery .= " WHERE \"id\" IN (" . rtrim(str_repeat('?,', count($requestIds)), ',') . ")";

        $bindings = array_merge($bindings, $requestIds);

        DB::update($updateQuery, $bindings);
    }

    /**
     * Applies multiple revisions in a single query.
     *
     * @param array $revisionIds
     */
    public function bulkApplyRevisions(array $revisionIds): void
    {
        if (empty($revisionIds)) {
            return;
        }

        // Updated:
        // 1. Use the correct table name 'revisions'.
        // 2. Set status via Enum for reliability.
        // 3. Add 'deleted_at' to simulate soft deletion, as in the setApplied() method.
        DB::table('revisions')
            ->whereIn('id', $revisionIds)
            ->update([
                         'status' => RevisionStatus::APPLIED->value,
                         'deleted_at' => now()
                     ]);
    }
}
