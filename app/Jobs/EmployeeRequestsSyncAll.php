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
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeRequestsSyncAll extends EHealthJob
{
    public const string BATCH_NAME = 'EmployeeRequestsSyncAll';
    public const string SCOPE_REQUIRED = 'employee_request:read';
    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        Log::info('[EmployeeRequestsSyncAll] Sending request for page ' . $this->page);

        return EHealth::employeeRequest()
            ->withToken($token)
            ->getMany(['edrpou' => $this->legalEntity->edrpou], $this->page);
    }

    protected function processResponse(?EHealthResponse $response): void
    {
        Log::info('[EmployeeRequestsSyncAll] Processing page ' . $this->page);

        $eHealthRequests = collect($response?->validate() ?? [])->keyBy('uuid');

        if ($eHealthRequests->isEmpty()) {
            return;
        }

        $localSignedRequests = EmployeeRequest::where('legal_entity_id', $this->legalEntity->id)
            ->where('status', LocalStatus::SIGNED)
            ->whereNull('applied_at')
            ->whereIn('uuid', $eHealthRequests->keys())
            ->with(['revision', 'employee.party'])
            ->get();

        if ($localSignedRequests->isEmpty()) {
            return;
        }

        $approvedGroup = collect();

        foreach ($localSignedRequests as $localRequest) {
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

        $groupedByEmployee = $approvedGroup->groupBy('employee_id');

        foreach ($groupedByEmployee as $employeeId => $requests) {
            /** @var EmployeeRequest $latestRequest */
            $latestRequest = $requests->sortByDesc('created_at')->first();

            if (!$latestRequest->employee) {
                Log::error("[EmployeeRequestsSyncAll] Skipping Request {$latestRequest->id}: Relation 'employee' is NULL for employee_id {$employeeId}.");
                continue;
            }

            $eHealthData = $eHealthRequests->get($latestRequest->uuid);

            Log::info("[EmployeeRequestsSyncAll] Trusting Revision from APPROVED Request {$latestRequest->uuid}");
            $this->applyRevisionUpdate($requests, $latestRequest, $eHealthData);
        }
    }

    /**
     * @throws \Throwable
     */
    private function applyRevisionUpdate($allRequestsInGroup, EmployeeRequest $latestRequest, array $eHealthData): void
    {
        DB::transaction(static function () use ($allRequestsInGroup, $latestRequest, $eHealthData) {
            $revisionData = $latestRequest->revision->data;
            $mappedLocalData = EHealth::employeeRequest()->mapCreate($revisionData);
            $employee = $latestRequest->employee;

            // Update Employee
            $systemOverrides = Arr::only($eHealthData, ['status', 'start_date', 'end_date', 'position', 'employee_type']);

            if (isset($eHealthData['division_id'])) {
                $systemOverrides['division_id'] = $eHealthData['division_id'];
            }

            $employee->update(array_merge(
                $mappedLocalData['employee'],
                $systemOverrides
            ));

            // Update Details
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
