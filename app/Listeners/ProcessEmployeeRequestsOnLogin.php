<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\Api\Employee;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Carbon;
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
            $employeesToUpsert = [];
            $requestsToUpsert = [];

            foreach ($pendingRequests as $request) {
                if ($approvedEmployeeMap->has($request->position)) {
                    $approvedData = $approvedEmployeeMap->get($request->position);

                    $employeesToUpsert[] = Employee::prepareEmployeeDataForDb(
                        $approvedData,
                        $event->legalEntity,
                        $event->user
                    );

                    // Prepare data for the bulk request update.
                    $requestsToUpsert[] = [
                        'id' => $request->id,
                        'status' => 'APPROVED',
                        'applied_at' => Carbon::parse($approvedData['updated_at'] ?? 'now')->toIso8601String()
                    ];

                    if ($request->revision) {
                        $request->revision->setApplied();
                    }
                }
            }

            if (!empty($employeesToUpsert)) {
                $this->employeeRepository->upsertEmployees($employeesToUpsert);
            }

            if (!empty($requestsToUpsert)) {
                $this->employeeRepository->upsertEmployeeRequests($requestsToUpsert);
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
