<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Enums\JobStatus;
use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Log;
use Throwable;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;

class EmployeeDetailsUpsert extends EHealthJob
{
    use Dispatchable;

    use SerializesModels;

    public const string BATCH_NAME = 'EmployeeDetailsSync';

    public const string SCOPE_REQUIRED = 'employee:details';

    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    public function __construct(
        public Employee $employee,
        public ?LegalEntity $legalEntity,
        protected ?EHealthJob $nextEntity = null,
        public bool $standalone = false,
    ) {
        parent::__construct(legalEntity: $legalEntity, nextEntity: $nextEntity, standalone: $standalone);
    }

    // Get data from EHealth API

    /**
     * @throws ConnectionException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        return EHealth::employee()->withToken($token)->getDetails($this->employee->uuid, groupByEntities: true);
    }

    // Store or update data in the database

    /**
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        $validatedData = $response->validate();

        echo 'Processing EmployeeDetailsUpsert for employee:' . $this->employee->id . ', LE:' . ($this->legalEntity->id ?? 'N/A') . PHP_EOL;

        $this->employee->save();
        Repository::employee()->updateDetails(
            $this->employee,
            $validatedData['party'],
            $validatedData['documents'],
            $validatedData['phones'],
            $validatedData['educations'] ?? null,
            $validatedData['specialities'] ?? null,
            $validatedData['qualifications'] ?? null,
            $validatedData['scienceDegree'] ?? null
        );

        $this->employee->setSyncStatus(JobStatus::COMPLETED);
        $this->employee->refresh();

        $user = $this->employee->user;

        if (!$user) {
            Log::info('Employee sync: User is not associated with this employee record yet.', [
                'employee_id' => $this->employee->id,
                'employee_uuid' => $this->employee->uuid,
            ]);

            return;
        }

        $roleName = $this->employee->employee_type;
        $legalEntityId = $this->employee->legal_entity_id;

        echo "Employee UUID: {$this->employee->uuid}, Role: {$roleName}, UserID: {$user->id}" . PHP_EOL;

        setPermissionsTeamId($legalEntityId);

        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-employee-get')
        ];
    }

    // Get next entity job if needed
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->nextEntity;
    }
}
