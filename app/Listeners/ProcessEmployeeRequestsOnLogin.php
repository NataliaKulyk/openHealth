<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\Api\EmployeeApi;
use App\Events\EHealthUserLogin;
use App\Repositories\EmployeeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Handles the EHealthUserLogin event to synchronize employee statuses.
 * This listener orchestrates the process of fetching approved employee data
 * from E-Health and updating the local database in bulk.
 */
readonly class ProcessEmployeeRequestsOnLogin
{
    /**
     * The constructor uses the 'readonly' property promotion for immutability.
     */
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        try {
            // Step 1: Find local pending requests using the repository.
            $pendingRequests = $this->employeeRepository->findPendingRequestsForUser($event->user, $event->legalEntity);

            if ($pendingRequests->isEmpty()) {
                return;
            }

            $userParty = $event->user->party;
            if (!$userParty) {
                return;
            }

            // Step 2: Make ONE optimized API call to fetch approved employees for this person.
            $filterParams = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'party_id' => $userParty->uuid,
            ];

            $employeesFromApi = EmployeeApi::getEmployeesList($filterParams);

            // Step 3: Filter results on our side (as API might ignore 'status') and create a map.
            $approvedEmployeeMap = collect($employeesFromApi)
                ->where('status', 'APPROVED')
                ->keyBy('position');

            if ($approvedEmployeeMap->isEmpty()) {
                return;
            }

            $employeesToUpsert = [];
            $requestsToUpsert = [];

            // Step 4: Prepare data arrays for bulk operations. No DB queries inside the loop.
            foreach ($pendingRequests as $request) {
                if ($approvedEmployeeMap->has($request->position)) {
                    $approvedData = $approvedEmployeeMap->get($request->position);

                    // Use the API class to transform the data into a DB-ready format.
                    $employeesToUpsert[] = EmployeeApi::prepareEmployeeDataForDb(
                        $approvedData,
                        $event->legalEntity,
                        $event->user
                    );

                    // Mark this local request for a status update.
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
            // Step 5: Perform bulk database operations after the loop.
            if (!empty($employeesToUpsert)) {
                $this->employeeRepository->upsertEmployees($employeesToUpsert);
            }

            if (!empty($requestsToUpsert)) {
                $this->employeeRepository->upsertEmployeeRequests($requestsToUpsert);
            }

        } catch (Throwable $e) {
            Log::error('Failed to process employee requests on login.', [
                'user_id' => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
