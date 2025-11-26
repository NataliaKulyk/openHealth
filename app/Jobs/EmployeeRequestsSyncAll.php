<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\Arr;
use App\Core\EHealthJob;
use App\Enums\Employee\RequestStatus as LocalStatus;
use App\Enums\Employee\RevisionStatus;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeRequestsSyncAll extends EHealthJob
{
    public const string BATCH_NAME = 'EmployeeRequestsSyncAll';
    public const string SCOPE_REQUIRED = 'employee_request:read';
    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    /**
     * Send request to fetch Employee Requests list.
     *
     * @throws ConnectionException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        Log::info('[EmployeeRequestsSyncAll] Sending request for page ' . $this->page);

        return EHealth::employeeRequest()
            ->withToken($token)
            ->getMany(['edrpou' => $this->legalEntity->edrpou], $this->page);
    }

    /**
     * Process the response.
     *
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        Log::info('[EmployeeRequestsSyncAll] Processing page ' . $this->page);

        // 1. Key eHealth data by UUID for easy lookup
        $eHealthRequests = collect($response?->validate() ?? [])->keyBy('uuid');

        if ($eHealthRequests->isEmpty()) {
            return;
        }

        // 2. Find matching local requests
        $localSignedRequests = EmployeeRequest::where('legal_entity_id', $this->legalEntity->id)
            ->where('status', LocalStatus::SIGNED)
            ->whereNull('applied_at')
            ->whereIn('uuid', $eHealthRequests->keys())
            ->with(['revision', 'employee.party'])
            ->get();

        if ($localSignedRequests->isEmpty()) {
            return;
        }

        // 3. Filter Approved items
        $approvedGroup = collect();

        foreach ($localSignedRequests as $localRequest) {
            // Retrieve status directly from the collection, DO NOT attach to model to avoid SQL errors
            $eHealthData = $eHealthRequests->get($localRequest->uuid);
            $status = $eHealthData['status'] ?? null;

            if ($status === 'APPROVED') {
                $approvedGroup->push($localRequest);
            } elseif (in_array($status, ['REJECTED', 'EXPIRED'])) {
                $newStatus = ($status === 'REJECTED') ? LocalStatus::REJECTED : LocalStatus::EXPIRED;
                $localRequest->update(['status' => $newStatus, 'applied_at' => now()]);
            }
        }

        if ($approvedGroup->isEmpty()) {
            return;
        }

        // 4. Group by Employee to handle chain of requests
        $groupedByEmployee = $approvedGroup->groupBy('employee_id');

        foreach ($groupedByEmployee as $employeeId => $requests) {
            /** @var EmployeeRequest $latestRequest */
            $latestRequest = $requests->sortByDesc('created_at')->first();

            // Get data from the source collection using the UUID of the latest request
            $eHealthData = $eHealthRequests->get($latestRequest->uuid);

            Log::info("[EmployeeRequestsSyncAll] Trusting Revision from APPROVED Request {$latestRequest->uuid}");
            $this->applyRevisionUpdate($requests, $latestRequest, $eHealthData);
        }
    }

    /**
     * @throws Throwable
     */
    private function applyRevisionUpdate($allRequestsInGroup, EmployeeRequest $latestRequest, array $eHealthData): void
    {
        DB::transaction(static function () use ($allRequestsInGroup, $latestRequest, $eHealthData) {
            $revisionData = $latestRequest->revision->data;
            $mappedLocalData = EHealth::employeeRequest()->mapCreate($revisionData);
            $employee = $latestRequest->employee;

            // Update Employee: Use Revision as source of truth for mutable fields,
            // but respect immutable fields if they are present in eHealth payload (like status, dates)
            $systemOverrides = Arr::only($eHealthData, ['status', 'start_date', 'end_date', 'position', 'employee_type']);

            // division_id is mutable, so if eHealth returns it, we can use it
            if (isset($eHealthData['division_id'])) {
                $systemOverrides['division_id'] = $eHealthData['division_id'];
            }

            $employee->update(array_merge(
                $mappedLocalData['employee'],
                $systemOverrides
            ));

            // Update Details: Revision is the absolute source of truth
            Repository::employee()->updateDetails(
                $employee,
                $mappedLocalData['party'],
                $mappedLocalData['documents'],
                $mappedLocalData['phones'],
                $mappedLocalData['educations'] ?? null,
                $mappedLocalData['specialities'] ?? null,
                $mappedLocalData['qualifications'] ?? null,
                $mappedLocalData['scienceDegree'] ?? null
            );

            foreach ($allRequestsInGroup as $req) {
                $req->update(['status' => LocalStatus::APPROVED, 'applied_at' => now()]);
                $req->revision?->update(['status' => RevisionStatus::APPLIED]);
            }
        });
    }

    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-employee-request-get')];
    }
}
