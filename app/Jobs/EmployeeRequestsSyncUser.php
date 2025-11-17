<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Traits\AppliesEmployeeRequestChanges;
use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Enums\Employee\RequestStatus as LocalStatus;
use App\Enums\JobStatus;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\User;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeRequestsSyncUser extends EHealthJob
{
    use AppliesEmployeeRequestChanges;

    public const string BATCH_NAME = 'EmployeeRequestsSyncUser';
    public const string SCOPE_REQUIRED = 'employee_request:read';
    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    protected ?array $uuidsToSync = null;
    protected ?Collection $localSignedRequests = null;

    /**
     * Create a new job instance.
     *
     * @param LegalEntity|null $legalEntity
     * @param User|null $user
     * @param string|null $manualToken Token passed directly for synchronous execution
     */
    public function __construct(
        public ?LegalEntity $legalEntity,
        public ?User $user,
        protected ?string $manualToken = null
    ) {
        parent::__construct($legalEntity);
    }

    /**
     * Explicitly run the job synchronously in the current process.
     * This bypasses the queue and uses the provided token immediately.
     *
     * @param LegalEntity $legalEntity
     * @param User $user
     * @param string $token
     * @return void
     */
    public static function dispatchSync(LegalEntity $legalEntity, User $user, string $token): void
    {
        $instance = new self($legalEntity, $user, $token);

        try {
            $instance->handle();
        } catch (Throwable $e) {
            Log::error('[EmployeeRequestsSyncUser] Synchronous execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute the job.
     *
     * COMPLETELY OVERLOAD THE PARENT HANDLE()
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        Log::info('[EmployeeRequestsSyncUser] Starting personal sync for user: ' . $this->user->id);

        $this->setEntityStatus(JobStatus::PROCESSING);

        try {
            if ($this->manualToken) {
                $this->token = $this->manualToken;
            } else {
                $this->token = Crypt::decryptString($this->batch()?->options['token'] ?? '');
            }

            if (empty($this->token)) {
                Log::error('[EmployeeRequestsSyncUser] Token is empty, cannot proceed.');
                $this->setEntityStatus(JobStatus::FAILED);
                return;
            }
        } catch (Throwable $e) {
            Log::error('[EmployeeRequestsSyncUser] Failed to decrypt/set token: ' . $e->getMessage());
            $this->fail($e);
            return;
        }

        $this->localSignedRequests = EmployeeRequest::where('legal_entity_id', $this->legalEntity->id)
            ->where('user_id', $this->user->id)
            ->where('status', LocalStatus::SIGNED)
            ->whereNull('applied_at')
            ->with(['revision', 'employee.party'])
            ->get();

        if ($this->localSignedRequests->isEmpty()) {
            Log::info('[EmployeeRequestsSyncUser] No local SIGNED requests found. Finishing.');
            $this->setEntityStatus(JobStatus::COMPLETED);
            return;
        }

        $this->uuidsToSync = $this->localSignedRequests->pluck('uuid')->filter()->all();

        if (empty($this->uuidsToSync)) {
            Log::info('[EmployeeRequestsSyncUser] No eHealth UUIDs found on local requests. Finishing.');
            $this->setEntityStatus(JobStatus::COMPLETED);
            return;
        }

        Log::info('[EmployeeRequestsSyncUser] Found ' . count($this->uuidsToSync) . ' requests to check.');

        $response = $this->sendRequest($this->token);

        $this->processResponse($response);

        Log::info('[EmployeeRequestsSyncUser] Personal sync finished for user: ' . $this->user->id);
        $this->setEntityStatus(JobStatus::COMPLETED);
    }

    /**
     * Sends the request to eHealth.
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse|null
     * @throws ConnectionException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        $uuidString = implode(',', $this->uuidsToSync);

        Log::info('[EmployeeRequestsSyncUser] Sending request for UUIDs: ' . $uuidString);

        return EHealth::employeeRequest()
            ->withToken($token)
            ->getMany(['uuid' => $uuidString], null);
    }

    /**
     * Processes the API response.
     *
     * @param  EHealthResponse|null  $response
     * @return void
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        Log::info('[EmployeeRequestsSyncUser] --- Starting to process response ---');
        $eHealthRequests = collect($response?->validate() ?? [])->keyBy('uuid');

        if ($eHealthRequests->isEmpty()) {
            Log::info('[EmployeeRequestsSyncUser] eHealth returned no data for the requested UUIDs.');
            return;
        }

        Log::info('[EmployeeRequestsSyncUser] Checking ' . $this->localSignedRequests->count() . ' local SIGNED requests against eHealth response.');

        $approvedRequestsToApply = collect();

        foreach ($this->localSignedRequests as $localRequest) {
            $eHealthData = $eHealthRequests->get($localRequest->uuid);
            if (!$eHealthData) {
                continue;
            }

            $eHealthStatus = $eHealthData['status'] ?? null;

            if ($eHealthStatus === 'APPROVED') {
                if ($this->shouldApplyUpdate($localRequest, $eHealthData)) {
                    $approvedRequestsToApply->push($localRequest);
                } else {
                    Log::warning('[EmployeeRequestsSyncUser] Request ' . $localRequest->uuid . ' is APPROVED but response is missing changed fields. Skipping update.');
                }
            } elseif (in_array($eHealthStatus, ['REJECTED', 'EXPIRED'])) {
                $newStatus = ($eHealthStatus === 'REJECTED') ? LocalStatus::REJECTED : LocalStatus::EXPIRED;
                $localRequest->update(['status' => $newStatus, 'applied_at' => now()]);
                Log::info('[EmployeeRequestsSyncUser] Request ' . $localRequest->uuid . ' status updated to ' . $eHealthStatus);
            }
        }

        if ($approvedRequestsToApply->isNotEmpty()) {
            Log::info('[EmployeeRequestsSyncUser] Found ' . $approvedRequestsToApply->count() . ' validated approved requests to process.');
            $groupedByEmployee = $approvedRequestsToApply->groupBy('employee_id');

            foreach ($groupedByEmployee as $employeeId => $requests) {
                $latestRequest = $requests->sortByDesc('created_at')->first();

                Log::info('[EmployeeRequestsSyncUser] Applying changes for employee ID ' . $employeeId . ' from the latest request: ' . $latestRequest->uuid);

                if ($this->applyChangesFromRevision($latestRequest)) {
                    EmployeeRequest::where('employee_id', $employeeId)
                        ->whereIn('id', $requests->pluck('id'))
                        ->update(['status' => LocalStatus::APPROVED, 'applied_at' => now()]);
                }
            }
        }

        Log::info('[EmployeeRequestsSyncUser] --- Finished processing response ---');
    }

    /**
     * Checks if the eHealth response contains at least one field that matches
     * the fields being changed in the local revision.
     *
     * @param EmployeeRequest $localRequest
     * @param array $eHealthData
     * @return bool
     */
    private function shouldApplyUpdate(EmployeeRequest $localRequest, array $eHealthData): bool
    {
        $revisionData = $localRequest->revision?->data ?? [];

        $changedKeys = [];

        $employeeData = $revisionData['employee_request_data'] ?? [];
        foreach (array_keys($employeeData) as $key) {
            $changedKeys[] = $key;
        }

        if (!empty($revisionData['party'])) {
            $changedKeys[] = 'party';
        }

        if (!empty($revisionData['doctor'])) {

            $changedKeys[] = 'doctor';
            $changedKeys[] = 'specialities';
        }

        $eHealthKeys = array_keys($eHealthData);

        $intersection = array_intersect($changedKeys, $eHealthKeys);

        if (!empty($intersection)) {
            return true;
        }

        return false;
    }

    /**
     * Get additional middleware.
     *
     * @return array
     */
    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-employee-request-get')];
    }
}
