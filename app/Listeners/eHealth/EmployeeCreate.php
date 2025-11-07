<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use App\Traits\FindsAndVerifiesPartyTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;
use Throwable;

class EmployeeCreate
{
    use FindsAndVerifiesPartyTrait;

    /**
     * @throws Throwable
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;

        $employeeRequests = EmployeeRequest::with('revision')
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($employeeRequests->isEmpty()) {
            return;
        }

        Log::info('[EmployeeCreate] Знайдено EmployeeRequests. Спроба прив\'язати User до існуючої Party.', ['user_id' => $user->id, 'email' => $user->email]);

        $revisionData = $employeeRequests->first()->revision->data['party'] ?? null;
        if (!$revisionData) {
            Log::error('[EmployeeCreate] EmployeeRequest не має revision data. Неможливо прив\'язати party.', ['request_id' => $employeeRequests->first()->id]);

            return;
        }

        $taxId = $revisionData['tax_id'] ?? null;
        $firstName = $revisionData['first_name'] ?? null;
        $lastName = $revisionData['last_name'] ?? null;
        $secondName = $revisionData['second_name'] ?? null;

        if (!$taxId || !$firstName || !$lastName) {
            Log::error('[EmployeeCreate] Revision data не містить taxId, firstName або lastName. Неможливо прив\'язати party.', ['request_id' => $employeeRequests->first()->id]);

            return;
        }

        // this trait will identify party by first_name, last_name, date_of_birth and tax_id
        $party = $this->findAndVerifyParty($taxId, $lastName, $firstName, $secondName);

        if ($party) {
            //  get party.
            $user->party()->associate($party);
            $user->save();
            $user->refresh(); // User update
            Log::info('[EmployeeCreate] УСПІШНО прив\'язано нового User до існуючої Party. Верифікацію КЕП буде пропущено.', ['user_id' => $user->id, 'party_id' => $party->id]);
        } else {
            // employee_request doesn`t match any party datea
            Log::warning('[EmployeeCreate] Дані з EmployeeRequest не збіглися з існуючою Party. Користувач буде відправлений на верифікацію КЕП.', ['user_id' => $user->id, 'tax_id' => $taxId]);

            return;
        }

        $taxId = $employeeRequests->first()->revision->data['party']['tax_id'];
        $employees = EHealth::employee()->getMany(
            [
                'legal_entity_id' => $event->legalEntity->uuid,
                'tax_id' => $taxId,
                'status' => 'APPROVED',
            ]
        )->validate();

        if (empty($employees)) {
            return;
        }

        // This filters out only uuids associated with the cuurent user
        $existingUuids = Employee::whereIn('uuid', array_column($employees, 'uuid'))
            ->where('legal_entity_id', $event->legalEntity->id)
            ->pluck('uuid')
            ->all();

        // Filter out employees that already exist in the local database
        $employees = array_filter($employees, fn (array $employee) => !in_array($employee['uuid'], $existingUuids));

        if (empty($employees)) {
            return;
        }

        $newRoles = [];

        DB::transaction(function () use ($user, $employees, $employeeRequests, $event, &$newRoles) {
            foreach ($employees as $eHealthEmployee) {

                Log::info('[EmployeeCreate] Обробляємо eHealthEmployee:', [
                    'ehealth_uuid' => $eHealthEmployee['uuid'] ?? 'N/A',
                    'position' => $eHealthEmployee['position'] ?? 'N/A',
                    'employee_type' => $eHealthEmployee['employee_type'] ?? 'N/A', // <-- ДИВИМОСЬ СЮДИ
                ]);

                $employeeRequest = $this->findMatchingLocalRequest($employeeRequests, $eHealthEmployee);

                if (!$employeeRequest) {
                    continue;
                }

                $dataFromRevision = EHealth::employeeRequest()->mapCreate($employeeRequest->revision->data);
                $dataFromEHealth = Arr::only($eHealthEmployee, ['uuid', 'status', 'position', 'employee_type', 'start_date', 'end_date', 'is_active']);

                $newEmployee = Employee::updateOrCreate(
                    ['uuid' => $dataFromEHealth['uuid']],
                    array_merge($dataFromRevision['employee'], $dataFromEHealth, [
                        'legal_entity_id' => $event->legalEntity->id,
                        'legal_entity_uuid' => $event->legalEntity->uuid,
                        'user_id' => $user->id
                    ])
                );

                $cleanPartyFromRevision = $dataFromRevision['party'];
                $cleanPartyFromEHealth = Arr::except($eHealthEmployee['party'] ?? [], ['email']);
                $mergedCleanPartyData = array_merge($cleanPartyFromRevision, $cleanPartyFromEHealth);

                $newEmployee = Repository::employee()->updateDetails(
                    $newEmployee,
                    $mergedCleanPartyData,
                    $dataFromRevision['documents'],
                    $dataFromRevision['phones'],
                    $dataFromRevision['educations'] ?? null,
                    $dataFromRevision['specialities'] ?? null,
                    $dataFromRevision['qualifications'] ?? null,
                    $dataFromRevision['scienceDegree'] ?? null
                );

                // Assign Party to User if not already assigned. OWNER ONLY!
                if ($user->hasRole('OWNER') && !$user?->party?->exists()) {
                    $user->partyId = $newEmployee->partyId;
                    $user->save();
                }

                $employeeRequest->update(
                    [
                        'employee_id' => $newEmployee->id,
                        'status' => RequestStatus::APPROVED,
                        'applied_at' => now(),
                        'user_id' => $user->id,
                        'party_id' => $newEmployee->partyId,
                    ]
                );

                $employeeRequest->revision->update(['status' => RevisionStatus::APPLIED]);

                if (!$user->hasRole($newEmployee->employeeType)) {
                    Log::info('[EmployeeCreate] Знайдена нова роль для додавання:', [
                        'employeeType' => $newEmployee->employeeType,
                    ]);
                    $newRoles[] = $newEmployee->employeeType;
                }
            }
        });

        if (!empty($newRoles)) {
            $cleanRoles = array_filter($newRoles, function ($roleName) {
                if (empty($roleName) || !is_string($roleName) || strtolower($roleName) === 'ehealth') {
                    Log::error('[EmployeeCreate] Спроба призначити некоректну або пусту роль.', ['roleName' => $roleName]);

                    return false;
                }

                return true;
            });

            if (empty($cleanRoles)) {
                Log::warning('[EmployeeCreate] Немає валідних ролей для призначення.', ['original_roles_list' => $newRoles]);

                return;
            }
            setPermissionsTeamId($event->legalEntity->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->assignRole($newRoles);
        }
    }

    /**
     * This matching logic is fragile as it relies on text fields.
     * A more robust solution would be to use a unique token exchanged during the signing process.
     * This implementation is kept for now but should be considered for a future upgrade.
     */
    private function findMatchingLocalRequest(Collection $employeeRequests, array $employee): ?EmployeeRequest
    {
        return $employeeRequests->where('position', $employee['position'])
            ->where('employee_type', $employee['employee_type'])
            ->first(function (EmployeeRequest $employeeRequest) use ($employee) {
                $party = $employeeRequest->revision->data['party'];
                $namesMatch = $party['first_name'] === $employee['party']['first_name']
                    && $party['last_name'] === $employee['party']['last_name']
                    && $party['second_name'] === $employee['party']['second_name'];

                $eHealthDateString = $employee['start_date'] ?? null;

                if (is_null($eHealthDateString) || is_null($employeeRequest->start_date)) {
                    return false;
                }

                $datesMatch = Carbon::parse($employeeRequest->start_date)
                    ->isSameDay(Carbon::parse($eHealthDateString));

                return $namesMatch && $datesMatch;
            });
    }
}
