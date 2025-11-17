<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Traits\AppliesEmployeeRequestChanges;
use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Enums\Employee\RequestStatus as LocalStatus;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeRequestsSyncAll extends EHealthJob
{
    use AppliesEmployeeRequestChanges;

    public const string BATCH_NAME = 'EmployeeRequestsSyncAll';
    public const string SCOPE_REQUIRED = 'employee_request:read';
    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    /**
     * Fetches a single page of employee requests from the eHealth API
     * for the current legal entity, filtered by its EDRPOU.
     *
     * @param  string  $token  The authorization token for the API request.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        Log::info('[EmployeeRequestsSyncAll] Sending request to eHealth for page ' . $this->page . ' for EDRPOU: ' . $this->legalEntity->edrpou);

        return EHealth::employeeRequest()
            ->withToken($token)
            ->getMany(['edrpou' => $this->legalEntity->edrpou], $this->page);
    }

    /**
     * Processes the API response for a page of employee requests.
     *
     * @param  EHealthResponse|null  $response  The API response object.
     * @return void
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        Log::info('[EmployeeRequestsSyncAll] --- Starting to process page ' . $this->page . ' ---');
        $eHealthRequests = collect($response?->validate() ?? [])->keyBy('uuid');

        if ($eHealthRequests->isEmpty()) {
            Log::info('[EmployeeRequestsSyncAll] No eHealth requests found on this page. Finishing job.');

            return;
        }
        Log::info('[EmployeeRequestsSyncAll] Received ' . $eHealthRequests->count() . ' requests from eHealth API.');

        $localSignedRequests = EmployeeRequest::where('legal_entity_id', $this->legalEntity->id)
            ->where('status', LocalStatus::SIGNED)
            ->whereNull('applied_at')
            ->whereIn('uuid', $eHealthRequests->keys())
            ->with(['revision', 'employee.party'])
            ->get();

        if ($localSignedRequests->isEmpty()) {
            Log::info('[EmployeeRequestsSyncAll] No local SIGNED requests match the UUIDs from eHealth on this page.');

            return;
        }
        Log::info('[EmployeeRequestsSyncAll] Found ' . $localSignedRequests->count() . ' matching SIGNED requests in local DB to check.');

        $approvedRequestsToApply = collect();

        foreach ($localSignedRequests as $localRequest) {
            $eHealthStatus = $eHealthRequests->get($localRequest->uuid)['status'] ?? null;

            if ($eHealthStatus === 'APPROVED') {
                $approvedRequestsToApply->push($localRequest);
            } elseif (in_array($eHealthStatus, ['REJECTED', 'EXPIRED'])) {
                $newStatus = ($eHealthStatus === 'REJECTED') ? LocalStatus::REJECTED : LocalStatus::EXPIRED;
                $localRequest->update(['status' => $newStatus, 'applied_at' => now()]);
                Log::info('[EmployeeRequestsSyncAll] Request ' . $localRequest->uuid . ' status updated to ' . $eHealthStatus);
            }
        }

        if ($approvedRequestsToApply->isNotEmpty()) {
            Log::info('[EmployeeRequestsSyncAll] Found ' . $approvedRequestsToApply->count() . ' approved requests to process.');
            $groupedByEmployee = $approvedRequestsToApply->groupBy('employee_id');

            foreach ($groupedByEmployee as $employeeId => $requests) {
                $latestRequest = $requests->sortByDesc('created_at')->first();
                Log::info('[EmployeeRequestsSyncAll] Applying changes for employee ID ' . $employeeId . ' from the latest request: ' . $latestRequest->uuid);

                if ($this->applyChangesFromRevision($latestRequest)) {
                    EmployeeRequest::where('employee_id', $employeeId)
                        ->whereIn('id', $requests->pluck('id'))
                        ->update(['status' => LocalStatus::APPROVED, 'applied_at' => now()]);
                }
            }
        }
        Log::info('[EmployeeRequestsSyncAll] --- Finished processing page ' . $this->page . ' ---');
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-employee-request-get')];
    }
}
