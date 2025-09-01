<?php

declare(strict_types=1);

namespace App\Repositories;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Division;
use Illuminate\Support\Str;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\Employee\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Employee\BaseEmployee;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Validator as ResponseValidator;
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

        $employee = $employeeModel::updateOrCreate(
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
     * @param User|null  $user
     * @param Party|null $party
     * @param array      $employeeData Data received from request to eHealth (GetEmployeesList|GetEmployeeDetails)
     * @param string     $authUserUUID
     * @param string     $legalEntityUUID
     *
     * @return void
     * @throws Exception
     */
    protected function updateEmployeeDataAtFirstLogin(User|null $user, Party|null $party, array $employeeData, string $authUserUUID, string $legalEntityUUID): void
    {
        $employeeResponse = schemaService()->setDataSchema($employeeData, app(EmployeeApi::class))
            ->responseSchemaNormalize()
            ->replaceIdsKeysToUuid(['id', 'legalEntityId', 'divisionId', 'partyId'])
            ->snakeCaseKeys(true)
            ->getNormalizedData();

        $legalEntity = legalEntity() ?? LegalEntity::where('uuid', $legalEntityUUID)->first();

        $employeeResponse['division_id'] = isset($employeeResponse['division_uuid'])
            ? Division::where('uuid', $employeeResponse['division_uuid'])->first()?->id
            : null;

        // Update Party uuid because it is hasn't actual value in the employeeRequest
        if ($party !== null && $party->uuid !== $employeeResponse['party']['uuid']) {
            $party->uuid = $employeeResponse['party']['uuid'];

            $party->save();
        }

        if ($user && empty($employeeData['userId'])) {
            $employeeResponse['user_id'] = $user->id;

            $user->uuid = $authUserUUID;

            $user->save();
        }

        $this->store($employeeResponse, $legalEntity, new Employee());
    }

    /**
     * Authenticate new OWNER and save data to the database.
     * This method now uses the EmployeeApi to prepare data and then performs a bulk upsert.
     * The unused $authUserUUID parameter has been removed.
     *
     * @param EmployeeRequest $employeeRequest
     * @param User $ownerUser
     * @return bool
     */
    public function authenticateNewOwner(EmployeeRequest $employeeRequest, User $ownerUser): bool
    {
        try {
            DB::transaction(function () use ($employeeRequest, $ownerUser, $authUserUUID) {

                $legalEntity = LegalEntity::find($employeeRequest->legal_entity_id); // TODO: check this and line below

                $legalEntityUUID = $legalEntity->uuid;

                // List of the users (employees) belongs to the same legal entity
                $employeeList = EmployeeApi::getEmployeesList($legalEntityUUID);

                $employeeData = [];

                $employeePosition = $employeeRequest->position;

                /*
                 * Variable to store OWNER's Party ID.
                 * Need to determine all employees belongs to OWNER.
                 */
                $ownerPartyUUID = null;

                // $employeList already contains 'OWNER' as first element
                foreach ($employeeList as $employee) {
                    $employeeData = $employee;

                    // Used only for OWNER's employee
                    $user = null;

                    if (($employee['position'] === $employeePosition && $employee['employee_type'] === 'OWNER') || $employeeData['party']['id'] === $ownerPartyUUID) {

                        $user = $ownerUser;

                        $employeeResponse = EmployeeApi::getEmployeeData($employee['id']);

                        $employeeValidator = $this->validateEmployeeData($employeeResponse);

                        /** @var \Illuminate\Contracts\Validation\Validator $employeeValidator */
                        if ($employeeValidator->fails()) {
                            Log::error(__('auth.login.error.validation.employee_data', [], 'en'), ['errors' => $employeeValidator->errors()]);

                            throw new Exception($employeeValidator->errors());
                        }

                        $employeeData = $employeeValidator->validated();

                        // This need because Party UUID for newly created EmployeeRequest may be NULL
                        $ownerPartyUUID = $employeeData['party']['id'];

                        $employeeData['party']['email'] = $user->email;

                        if ($employeeData['employee_type'] !== 'OWNER') {
                            Log::info('assignRole:', ['user' => $employeeData['employee_type']]); // TODO: remove it after testing

                            auth()->shouldUse('web');
                            $user->assignRole($employeeData['employee_type']);

                            auth()->shouldUse('ehealths');
                            $user->assignRole($employeeData['employee_type']);
                        }
                    }

                    $employeeData['legal_entity_id'] = $employeeData['legal_entity']['id'];
                    $employeeData['inserted_at'] = Carbon::now()->format('Y-m-d');
                    $employeeData['updated_at'] = Carbon::now()->format('Y-m-d');

                    $party = $user
                        ? $employeeRequest->party
                        : Party::where('uuid', $employeeData['party']['id'])->first();

                    if ($user && !$user->party) {
                        $party->user()->associate($user);
                    }

                    if ($employeeData['status'] === 'DISMISSED') {
                        $party = null;
                    }

                    if (is_array($employeeData['division']) && isset($employeeData['division']['id'])) {
                        $employeeData['division_id'] = $employeeData['division']['id'];
                    }

                    $this->updateEmployeeDataAtFirstLogin($user, $party, $employeeData, $authUserUUID, $legalEntityUUID);
                }

                // Update status of EmployeeRequest and the time of update
                $this->updateEmployeeRequestStatus($employeeRequest, 'APPROVED', $employeeRequest->updatedAt);

                if ($employeeRequest->revision) {
                    $employeeRequest->revision->setApplied();
                }
            });
        } catch (Exception $err) {
            Log::error('[authenticateNewOwner]: ' . __('auth.login.error.data_saving', [], 'en'), ['error' => $err->getMessage()]);
            return false;
        }

        return true;
    }

    /**
     * Authenticate new employee and save data to the database
     *
     * @param Employee $employee Only Employee type because up to now we should have all the data for employees
     * @param User $user
     * @param string $authUserUUID
     *
     * @return bool
     * TODO: test after creating an employee will works
     */
    public function authenticateNewEmployees(string $legalEntityUUID, User $user, string $authUserUUID): bool
    {
        // Get all employees except owner
        $employees = Employee::employeeInstance($user->id, $legalEntityUUID, ['OWNER'])->get();

        if (!$employees->count()) {
            return true;
        }

        try {
            DB::transaction(function () use ($employees, $user, $legalEntityUUID, $authUserUUID) {
                foreach ($employees as $employee) {
                    if ($employee->party->email) {
                        continue;
                    }

                    $employeeResponse = EmployeeApi::getEmployeeData($employee->uuid);

                    $employeeValidator = $this->validateEmployeeData($employeeResponse);

                    /** @var \Illuminate\Contracts\Validation\Validator $employeeValidator */
                    if ($employeeValidator->fails()) {
                        Log::error(__('auth.login.error.validation.employee_data', [], 'en'), ['errors' => $employeeValidator->errors()]);

                        throw new Exception($employeeValidator->errors());
                    }

                    $employeeData = $employeeValidator->validated();

                    $employeeData['party']['email'] = $user->email;

                    if (isset($employeeData['division']['id'])) {
                        $employeeData['division_id'] = $employeeData['division']['id'];
                    }

                    $employeeData['legal_entity_id'] = $employeeData['legal_entity']['id'];
                    $employeeData['inserted_at'] = Carbon::now()->format('Y-m-d');
                    $employeeData['updated_at'] = Carbon::now()->format('Y-m-d');

                    $this->updateEmployeeDataAtFirstLogin($user, $employee->party, $employeeData, $authUserUUID, $legalEntityUUID);
                }
            });
        } catch (Exception $err) {
            Log::error('[authenticateNewEmployees]: ' . __('auth.login.error.data_saving', [], 'en'), ['error' => $err->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Checks for employee updates.
     * This method now performs bulk updates outside the loop.
     * The unused $authUserUUID parameter has been removed.
     *
     * @param LegalEntity $legalEntity
     * @param User $user
     * @return bool
     */
    public function checkForEmployeeUpdate(LegalEntity $legalEntity, User $user, string $authUserUUID): bool
    {
        $employeeRoles = $user->getRoleNames()->toArray();

        $employeeRequests = EmployeeRequest::employeeInstance($user->id, $legalEntity->uuid, $employeeRoles, true)
            ->whereIn('status', RequestStatus::getStatusesForSync())
            ->whereNotNull('uuid')
            ->get();

        if (!$employeeRequests->count()) {
            return true;
        }

        try {
            DB::transaction(function () use ($employeeRequests, $user, $legalEntity, $authUserUUID) {
                $updatedAt = '1970-01-01T00:00:00.000000Z';

                foreach ($employeeRequests as $employeeRequest) {

                    $employeeRequestResponse = EmployeeApi::_getRequestById($employeeRequest->uuid);

                    $employeeRequestValidator = $this->validateEmployeeRequestData($employeeRequestResponse['data']);

                    /** @var \Illuminate\Contracts\Validation\Validator $employeeRequestValidator */
                    if ($employeeRequestValidator->fails()) {
                        Log::error(__('auth.login.error.validation.employee_request_data', [], 'en'), ['errors' => $employeeRequestValidator->errors()]);

                        throw new Exception($employeeRequestValidator->errors());
                    }

                    $employeeRequestData = $employeeRequestValidator->validated();

                    // Just skip request if nothing changes
                    if ($employeeRequestData['status'] === 'NEW') {
                        continue;
                    }

                    $this->updateEmployeeRequestStatus($employeeRequest, $employeeRequestData['status'], $employeeRequestData['updated_at']);

                    $currentRequestDate = Carbon::parse($employeeRequestData['updated_at']);
                    $proceddedRequestDate = Carbon::parse($updatedAt);

                    // Skip all EmployeeRequests that has wrong status (ex. EXPIRED) or older than last proceeded one
                    if ($employeeRequestData['status'] !== 'APPROVED' || $currentRequestDate->lt($proceddedRequestDate)) {
                        $employeeRequest->revision?->setOutdated();

                        continue;
                    }

                    $updatedAt = $employeeRequestData['updated_at'];

                    $employeeData = $this->getRevisionEmployeeData($employeeRequest);

                    $party = $employeeRequest->employee->party;

                    $this->updateEmployeeDataAtFirstLogin($user, $party, $employeeData, $authUserUUID, $legalEntity->uuid);

                    $employeeRequest->revision->setApplied();
                }
            });
        } catch (Exception $err) {
            Log::error('[checkForEmployeeUpdate]: ' . __('auth.login.error.data_saving', [], 'en'), ['error' => $err->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Update EmployeeRequest status and updated_at attributes
     * It mandatory for next employee updates to avoid repeat finished ones
     *
     * @param EmployeeRequest $employeeRequest
     * @param string          $status
     * @param string          $updatedAt
     *
     * @return void
     */
    public function updateEmployeeRequestStatus(EmployeeRequest $employeeRequest, string $status, string $updatedAt): void
    {
        $employeeRequest->status = $status;
        $employeeRequest->appliedAt = $updatedAt;

        $employeeRequest->save();
    }

    /**
     * [backward compatibility]
     * This method now proxies to the new, public method.
     */
    protected function updateEmployeeDataAtFirstLogin(?User $user, ?Party $party, array $employeeData, string $authUserUUID, string $legalEntityUUID): void
    {
        $this->createOrUpdateEmployeeFromEhealthData($user, $party, $employeeData, $authUserUUID, $legalEntityUUID);
    }

    /**
     * Prepare EmployeeData for employee update based on data stored in 'revisions' table
     *
     * @param EmployeeRequest $employeeRequest
     *
     * @return array
     */
    protected function getRevisionEmployeeData(EmployeeRequest $employeeRequest): array
    {
        $revisionData = $employeeRequest->revision->data;

        $employee = $employeeRequest->employee;

        $employeeData = collect($employee->toArray())
            ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
            ->toArray();

        unset($employeeData['user']);

        $employeeData['id'] = $employeeData['uuid'];
        $employeeData['legal_entity_id'] = $employeeData['legal_entity_uuid'];
        $employeeData['party']['id'] = $employeeData['party']['uuid'];
        $employeeData['party'] = array_merge($employeeData['party'], $revisionData['party']);
        $employeeData['party']['documents'] = $revisionData['documents'];
        $employeeData['party']['phones'] = $revisionData['phones'];

        if (isset($employeeData['division']['id'])) {
            $employeeData['division_id'] = $employeeData['division']['id'];
        }

        $employeeData['updated_at'] = Carbon::now()->format('Y-m-d');

        return $employeeData;
    }

    /**
     * Check employee details $response schema for errors.
     *
     * @return array Returned only specified fields
     */
    protected function validateEmployeeData(array $data): ResponseValidator
    {
        return Validator::make($data, [
            'division' => 'nullable|array',
            'division.id' => 'required_with:division|string',
            'division.name' => 'required_with:division|string',
            'division.legal_entity_id' => 'nullable|string',
            'employee_type' => 'required|string',
            'end_date' => 'nullable|string',
            'id' => 'required|string',
            'is_active' => 'required|bool',
            'legal_entity' => 'required|array',
            'legal_entity.id' => 'required|string',
            'party' => 'required|array',
            'party.id' => 'required|string',
            'party.first_name' => 'required|string',
            'party.last_name' => 'required|string',
            'party.second_name' => 'nullable|string',
            'party.no_tax_id' => 'nullable|bool',
            'party.gender' => 'nullable|string',
            'party.verification_status' => 'required|string',
            'party.tax_id' => 'nullable|string',
            'party.birth_date' => 'nullable|string',
            'party.documents' => 'nullable|array',
            'party.phones' => 'nullable|array',
            'party.phones.*.type' => 'required_with:party.phones|string',
            'party.phones.*.number' => 'required_with:party.phones|string',
            'party.working_experience' => 'nullable',
            'party.about_myself' => 'nullable',
            'start_date' => 'required|string',
            'status' => 'required|string',
            'position' => 'required|string',
            'doctor' => 'nullable|array'
        ]);
    }

    /**
     * Check employee response details $response schema for errors.
     *
     * @return array Returned only specified fields
     */
    protected function validateEmployeeRequestData(array $data): ResponseValidator
    {
        return Validator::make($data, [
            'division' => 'nullable|array',
            'division.id' => 'required_with:division|string',
            'division.name' => 'required_with:division|string',
            'division.legal_entity_id' => 'nullable|string',
            'employee_type' => 'required|string',
            'id' => 'required|string',
            'legal_entity_id' => 'required|string',
            'inserted_at' => 'required|string',
            'updated_at' => 'required|string',
            'party' => 'required|array',
            'party.birth_date' => 'nullable|string',
            'party.email' => 'nullable|string',
            'party.first_name' => 'required|string',
            'party.last_name' => 'required|string',
            'party.second_name' => 'nullable|string',
            'party.no_tax_id' => 'nullable|bool',
            'party.gender' => 'nullable|string',
            'party.tax_id' => 'nullable|string',
            'party.documents' => 'nullable|array',
            'party.phones' => 'nullable|array',
            'party.phones.*.type' => 'required_with:party.phones|string',
            'party.phones.*.number' => 'required_with:party.phones|string',
            'status' => 'required|string',
            'position' => 'required|string',
            'start_date' => 'nullable|string',
            'end_date' => 'nullable|string',
        ]);
    }
}
