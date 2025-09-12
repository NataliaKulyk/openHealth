<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function handle(EHealthUserLogin $event): void
    {
        if (!$event->user?->party?->uuid) {
            return;
        }

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);
        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            $ehealthResponse = EHealth::employee()->getMany(
                [
                    'legal_entity_id' => $event->legalEntity->uuid,
                    'party_id'        => $event->user->party->uuid,
                    'status'          => Status::APPROVED->value,
                ]
            );

            $employeesFromApi = $ehealthResponse->getData();
            if (empty($employeesFromApi)) {
                return;
            }

            $approvedEmployeesByPosition = collect($employeesFromApi)->groupBy('position');

            foreach ($pendingRequests as $request) {
                try {
                    if (!$approvedEmployeesByPosition->has($request->position)) {
                        continue;
                    }

                    $approvedData = $approvedEmployeesByPosition->get($request->position)
                        ?->firstWhere('employee_type', $request->employee_type);

                    if (!$approvedData) {
                        continue;
                    }

                    $detailsResponse = EHealth::employee()->getDetails($approvedData['id'], null, true);

                    $validatedData = $detailsResponse->validate();


                    DB::transaction(function () use ($validatedData, $request) {
                        $employeeModel = Employee::updateOrCreate(
                            ['uuid' => $validatedData['employee']['uuid']],
                            $validatedData['employee']
                        );

                        Repository::employee()->updateDetails(
                            $employeeModel,
                            $validatedData['party'],
                            $validatedData['documents'],
                            $validatedData['phones'],
                            $validatedData['educations'],
                            $validatedData['specialities'],
                            $validatedData['qualifications'],
                            $validatedData['scienceDegree']
                        );

                        $request->status = Status::APPROVED->value;
                        $request->applied_at = now();
                        $request->employee()->associate($employeeModel);
                        $request->save();
                    });

                } catch (Throwable $e) {
                    Log::error('Failed to process a single pending employee request.', [
                        'employee_request_id' => $request->id,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        } catch (Throwable $e) {
            Log::error('Failed to fetch initial list for employee requests processing.', [
                'user_id'         => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message'   => $e->getMessage(),
            ]);
        }
    }
}
