<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Livewire\Employee\Forms\EmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class EmployeeComponent extends Component
{
    use FormTrait {
        getDictionary as traitGetDictionary;
    }

    public EmployeeForm $form;
    public bool $isPersonalDataLocked = false;
    public bool $isPositionDataLocked = false;

    // Locks only IMMUTABLE fields (Position, Type, StartDate)
    // Allows editing: Division
    public bool $isCorePositionDataLocked = false;
    #[Locked]
    public ?int $employeeRequestId = null;
    public array $divisions = [];
    public bool $showSignatureModal = false;

    public ?array $dictionaryNames = [
        'PHONE_TYPE', 'COUNTRY', 'SETTLEMENT_TYPE', 'SPECIALITY_TYPE', 'DIVISION_TYPE',
        'SPECIALITY_LEVEL', 'GENDER', 'QUALIFICATION_TYPE', 'SCIENCE_DEGREE', 'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE', 'EMPLOYEE_TYPE', 'POSITION', 'EDUCATION_DEGREE', 'DIVISION'
    ];

    public ?array $dictionaries = [];
    public array $employeeTypePosition = [];
    public array $employeeTypeSpecialities = [];
    public array $employeeTypeLevels = [];
    public array $employeeTypeDegrees = [];
    public array $employeeTypeQualifications = [];
    public array $employeeTypeSpecQualifications = [];

    /**
     * This is the single, public method that child components will call.
     */
    public function loadDictionaries(): void
    {
        $this->getDictionary();
    }

    /**
     * The protected getDictionary method contains the implementation.
     */
    protected function getDictionary(): void
    {
        $this->traitGetDictionary();

        if (legalEntity()) {
            $allowedEmployeeTypes = config('ehealth.legal_entity_employee_types.' . legalEntity()->type->name, []);

            $this->dictionaries['EMPLOYEE_TYPE'] = array_intersect_key(
                $this->dictionaries['EMPLOYEE_TYPE'] ?? [],
                array_flip($allowedEmployeeTypes)
            );

            foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {

                $allowedQualKeys = config("ehealth.employee_type.{$employeeType}.qualification_type", []);
                $masterQualDict = $this->dictionaries['QUALIFICATION_TYPE'] ?? [];
                $this->employeeTypeQualifications[$employeeType] = array_intersect_key($masterQualDict, array_flip($allowedQualKeys));

                $allowedSpecQualKeys = config("ehealth.employee_type.{$employeeType}.speciality_qualification_type", []);
                $masterSpecQualDict = $this->dictionaries['SPEC_QUALIFICATION_TYPE'] ?? [];
                $this->employeeTypeSpecQualifications[$employeeType] = array_intersect_key($masterSpecQualDict, array_flip($allowedSpecQualKeys));

                $allowedPositionKeys = config("ehealth.employee_type.{$employeeType}.position", []);
                $masterPositionDict = $this->dictionaries['POSITION'] ?? [];
                $this->employeeTypePosition[$employeeType] = array_intersect_key($masterPositionDict, array_flip($allowedPositionKeys));

                $allowedSpecialityKeys = config("ehealth.employee_type.{$employeeType}.speciality_type", []);
                $masterSpecialityDict = $this->dictionaries['SPECIALITY_TYPE'] ?? [];
                $this->employeeTypeSpecialities[$employeeType] = array_intersect_key($masterSpecialityDict, array_flip($allowedSpecialityKeys));

                $allowedLevelKeys = config("ehealth.employee_type.{$employeeType}.speciality_level", []);
                $masterLevelDict = $this->dictionaries['SPECIALITY_LEVEL'] ?? [];
                $this->employeeTypeLevels[$employeeType] = array_intersect_key($masterLevelDict, array_flip($allowedLevelKeys));

                $allowedDegreeKeys = config("ehealth.employee_type.{$employeeType}.education_degree", []);
                $masterDegreeDict = $this->dictionaries['EDUCATION_DEGREE'] ?? [];
                $this->employeeTypeDegrees[$employeeType] = array_intersect_key($masterDegreeDict, array_flip($allowedDegreeKeys));
            }
        }
    }

    #[Computed]
    public function employeeFullName(): string
    {
        if (isset($this->employee) && $this->employee->party) {
            return $this->employee->party->fullName;
        }

        if (isset($this->party)) {
            return $this->party->fullName;
        }

        if (!empty($this->form->party['lastName'])) {
            return trim($this->form->party['lastName'] . ' ' . $this->form->party['firstName']);
        }

        return '';
    }

    protected function loadDivisions(LegalEntity $legalEntity): void
    {
        $this->divisions = $legalEntity->divisions()->where('status', Status::ACTIVE)->get(['id', 'name'])->toArray();
    }

    /**
     * Core logic to synchronize a single employee with eHealth.
     */
    protected function syncEmployeeData(Employee $employee): bool
    {
        // 1. Validation via Policy
        if (Gate::denies('sync', $employee)) {
            session()?->flash('error', 'Синхронізація недоступна для цього співробітника.');
            return false;
        }

        try {
            // 2. API Request (Token is handled automatically by EHealth client)
            $response = EHealth::employee()
                ->getDetails($employee->uuid, groupByEntities: true);

            $validatedData = $response->validate();

            // 3. Database Update via Repository
            Repository::employee()->updateDetails(
                $employee,
                $validatedData['party'],
                $validatedData['documents'],
                $validatedData['phones'],
                $validatedData['educations'] ?? null,
                $validatedData['specialities'] ?? null,
                $validatedData['qualifications'] ?? null,
                $validatedData['scienceDegree'] ?? null
            );

            // 4. Close/Actualize Pending Requests
            $this->actualizePendingRequests($employee);

            session()?->flash('success', 'Дані співробітника успішно оновлено з ЕСОЗ');

            return true;

        } catch (\Exception $e) {
            Log::error('Single Employee Sync Failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', 'Помилка синхронізації: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Checks "hanging" requests (SIGNED) for this employee in eHealth.
     * Refactored to batch process requests and avoid N+1 queries.
     */
    protected function actualizePendingRequests(Employee $employee): void
    {
        // Fetch local requests that need verification
        $pendingRequests = EmployeeRequest::where('employee_id', $employee->id)
            ->where('status', RequestStatus::SIGNED)
            ->whereNull('applied_at')
            ->get();

        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            // Batch API Request: Get all requests for this employee by UUID
            $response = EHealth::employeeRequest()
                ->getMany(
                    [
                        'employee_id' => $employee->uuid,
                    ]
                );

            // Map response by UUID for O(1) lookup
            $remoteRequests = collect($response->validate())->keyBy('uuid');

            foreach ($pendingRequests as $localRequest) {
                // Find matching remote request
                $remoteRequestData = $remoteRequests->get($localRequest->uuid);

                if (!$remoteRequestData) {
                    continue;
                }

                $remoteStatus = $remoteRequestData['status'] ?? null;

                // Update local status based on remote status
                if ($remoteStatus === 'APPROVED') {
                    $localRequest->update(
                        [
                            'status'     => RequestStatus::APPROVED,
                            'applied_at' => now(),
                        ]
                    );
                    $localRequest->revision?->update(['status' => RevisionStatus::APPLIED]);

                } else if (in_array($remoteStatus, ['REJECTED', 'EXPIRED'])) {
                    $newStatus = ($remoteStatus === 'REJECTED') ? RequestStatus::REJECTED : RequestStatus::EXPIRED;
                    $localRequest->update(
                        [
                            'status'     => $newStatus,
                            'applied_at' => now(),
                        ]
                    );
                }
            }

        } catch (\Exception $e) {
            Log::warning("Failed to batch check status for requests of employee {$employee->id}: " . $e->getMessage());
        }
    }
}
