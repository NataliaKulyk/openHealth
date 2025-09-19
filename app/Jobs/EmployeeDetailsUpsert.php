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
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class EmployeeDetailsUpsert implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

        $response = EHealth::employee()->withToken($this->token)->getDetails($this->employee->uuid, groupByEntities: true);
        $validatedData = $response->validate();

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

        $this->employee->refresh();

        $user = $this->employee->user;
        $roleName = $this->employee->employee_type;
        $legalEntityId = $this->employee->legal_entity_id;

        if ($user && $roleName && $legalEntityId) {
            setPermissionsTeamId($legalEntityId);

            $user->unsetRelation('roles')->unsetRelation('permissions');

            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

        }
    }
}
