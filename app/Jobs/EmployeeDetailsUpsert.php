<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Employee\Employee;
use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeDetailsUpsert implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Employee $employee,
        public User $user,
        protected string $token
    ) {
    }


    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        sleep(1);

        try {
            $response = EHealth::employee()->withToken($this->token)->getDetails($this->employee->uuid, groupByEntities: true);
            $validatedData = $response->validate();

            Repository::employee()->updateDetails(
                $this->employee,
                $validatedData['party'] ?? [],
                $validatedData['documents'] ?? [],
                $validatedData['phones'] ?? [],
                $validatedData['educations'] ?? null,
                $validatedData['specialities'] ?? null,
                $validatedData['qualifications'] ?? null,
                $validatedData['scienceDegree'] ?? null
            );

        } catch (Throwable $e) {

            if ($e instanceof ValidationException) {
                Log::error('E-Health data validation failed permanently for employee.', [
                    'employee_uuid' => $this->employee->uuid,
                    'errors' => $e->errors(),
                ]);
                $this->fail($e);
                return;
            }

            Log::warning('A throwable occurred in EmployeeDetailsUpsert job. Will attempt to retry.', [
                'employee_uuid' => $this->employee->uuid,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
