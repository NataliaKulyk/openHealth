<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\Api\Employee;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private Employee $employeeApi
    ) {
    }

    public function handle(EHealthUserLogin $event): void
    {
        try {
            $pendingRequests = $this->employeeRepository->findPendingRequestsForUser($event->user, $event->legalEntity);
            if ($pendingRequests->isEmpty()) return;

            $userParty = $event->user->party;
            if (!$userParty) return;

            $filterParams = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'party_id' => $userParty->uuid,
                'status' => Status::APPROVED->value
            ];

            $employeesFromApi = $this->employeeApi->getMany($filterParams);
            if (empty($employeesFromApi)) return;

            $approvedEmployeeMap = collect($employeesFromApi)->keyBy('position');

            // We will perform all operations for a single request inside a transaction.
            foreach ($pendingRequests as $request) {
                if ($approvedEmployeeMap->has($request->position)) {
                    DB::transaction(function () use ($request, $approvedEmployeeMap, $event) {
                        $approvedData = $approvedEmployeeMap->get($request->position);

                        // 1. Create/update the Employee and get the model back.
                        $employeeData = Employee::prepareEmployeeDataForDb(
                            $approvedData,
                            $event->legalEntity,
                            $event->user
                        );
                        $employee = $this->employeeRepository->createOrUpdateEmployee($employeeData);

                        // 2. Prepare data for the EmployeeRequest update.
                        $requestUpdateData = [
                            'status' => 'APPROVED',
                            'applied_at' => Carbon::parse($approvedData['updated_at'] ?? 'now')->toIso8601String(),
                            'employee_id' => $employee->id,
                            'legal_entity_uuid' => $request->legal_entity_uuid,
                            'inserted_at' => $request->inserted_at,
                        ];

                        // 3. Update the EmployeeRequest.
                        $this->employeeRepository->updateEmployeeRequest($request, $requestUpdateData);

                        if ($request->revision) {
                            // This still runs inside the transaction, which is safe.
                            $request->revision->setApplied();
                        }
                    });
                }
            }
        } catch (Throwable $e) {
            Log::error('Failed to process employee requests on login.', [
                'user_id' => $event->user->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
