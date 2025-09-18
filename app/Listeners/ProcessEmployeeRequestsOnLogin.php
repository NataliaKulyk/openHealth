<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function handle(EHealthUserLogin $event): void
    {
        $userParty = $event->user?->party;

        if (!$userParty || !$userParty->uuid) {
            return;
        }

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);
        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            $apiFilters = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'status' => Status::APPROVED->value,
                'party_id' => $userParty->uuid
            ];

            $ehealthResponse = EHealth::employee()->getMany($apiFilters);
            $employeesFromApi = $ehealthResponse->getData();

        } catch (Throwable $e) {
            Log::error('Failed to fetch initial list for employee requests processing.', [
                'user_id' => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($employeesFromApi)) {
            return;
        }

        $apiEmployeesCollection = collect($employeesFromApi);

        foreach ($pendingRequests as $request) {
            try {
                $matchIndex = $apiEmployeesCollection->search(function ($apiEmployee) use ($request) {
                    return $apiEmployee['position'] === $request->position
                        && $apiEmployee['employee_type'] === $request->employee_type;
                });

                if ($matchIndex === false) {
                    continue;
                }

                $approvedData = $apiEmployeesCollection->pull($matchIndex);

                $employeeData = [
                    'uuid' => Arr::get($approvedData, 'id'),
                    'status' => Arr::get($approvedData, 'status'),
                    'position' => Arr::get($approvedData, 'position'),
                    'employee_type' => Arr::get($approvedData, 'employee_type'),
                    'start_date' => Arr::get($approvedData, 'start_date'),
                    'end_date' => Arr::get($approvedData, 'end_date'),
                    'is_active' => Arr::get($approvedData, 'is_active', true),
                    'legal_entity_uuid' => Arr::get($approvedData, 'legal_entity.id'),
                ];

                $employeeData['legal_entity_id'] = $request->legal_entity_id;
                $employeeData['party_id'] = $request->party_id;
                $employeeData['user_id'] = $request->user_id;
                $employeeData['division_id'] = $request->division_id;

                $apiPartyData = Arr::get($approvedData, 'party', []);
                $partyData = [
                    'uuid' => Arr::get($apiPartyData, 'id'),
                    'first_name' => Arr::get($apiPartyData, 'first_name'),
                    'last_name' => Arr::get($apiPartyData, 'last_name'),
                    'second_name' => Arr::get($apiPartyData, 'second_name'),
                    'birth_date' => Arr::get($apiPartyData, 'birth_date'),
                    'gender' => Arr::get($apiPartyData, 'gender'),
                    'no_tax_id' => Arr::get($apiPartyData, 'no_tax_id'),
                    'tax_id' => Arr::get($apiPartyData, 'tax_id'),
                    'email' => Arr::get($apiPartyData, 'email'),
                ];
                $documents = Arr::get($apiPartyData, 'documents', []);
                $phones = Arr::get($apiPartyData, 'phones', []);

                $doctorDataKey = strtolower(Arr::get($approvedData, 'employee_type', ''));
                $doctorData = Arr::get($approvedData, $doctorDataKey, []);

                DB::transaction(
                    static function () use ($employeeData, $partyData, $documents, $phones, $doctorData, $request) {
                        $employeeModel = Employee::updateOrCreate(
                            ['uuid' => $employeeData['uuid']],
                            $employeeData
                        );

                        Repository::employee()->updateDetails(
                            $employeeModel,
                            $partyData,
                            $documents,
                            $phones,
                            Arr::get($doctorData, 'educations'),
                            Arr::get($doctorData, 'specialities'),
                            Arr::get($doctorData, 'qualifications'),
                            Arr::get($doctorData, 'scienceDegree')
                        );

                        $request->status = Status::APPROVED->value;
                        $request->applied_at = now();
                        $request->employee()->associate($employeeModel);
                        $request->save();

                        if ($request->revision()->exists()) {
                            $request->revision->update(['status' => RevisionStatus::APPLIED->value]);
                        }
                    }
                );

            } catch (Throwable $e) {
                Log::error('Failed to process a single pending employee request.', [
                    'employee_request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }
    }
}
