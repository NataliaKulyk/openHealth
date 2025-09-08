<?php

namespace App\Jobs;

use App\Core\Arr;
use App\Models\Employee;
use App\Repositories\Repository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Classes\eHealth\Api\Employee as Api;
use App\Classes\eHealth\EHealth;

class EmployeeDetailsUpsert implements ShouldQueue
{
    use Batchable, Queueable;

    public int $rest = Api::TIME_COOLDOWN;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Employee $employee
    )
    {}

    /**
     * Based on the updated employees, proccess all other related data through the EHealth get employee details endpoint.
     * Including
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $response = EHealth::employee()->getDetails($this->employee->uuid);
        $employee = $response->validate();
        $party = Arr::pull($employee, 'party');
        $employee->party()->updateOrCreate($party);
    }
}
