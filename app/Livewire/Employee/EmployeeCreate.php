<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Core\Arr;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\View\View;

class EmployeeCreate extends AbstractEmployeeFormManager
{
    public function mount(LegalEntity $legalEntity): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->isPersonalDataLocked = false;
    }

    protected function handleDraftPersistence(): EmployeeRequest
    {
        $preparedData = $this->form->getPreparedData();
        $nestedDataForRevision = $this->mapRevisionData($preparedData);

        // Prepare the data for the request model itself
        $employeeRequestData = Arr::only($preparedData, [
            'position', 'start_date', 'end_date', 'employee_type', 'division_id', 'email'
        ]);

        // Check if a draft already exists for this form session
        if ($this->employeeRequestId) {
            $existingRequest = EmployeeRequest::find($this->employeeRequestId);

            // Ensure we are only updating an unsigned draft
            if ($existingRequest && is_null($existingRequest->uuid)) {
                // Update the main request attributes
                $existingRequest->fill($employeeRequestData)->save();

                // Update the associated revision with the latest form data
                $existingRequest->revision?->update(['data' => $nestedDataForRevision]);

                return $existingRequest;
            }
        }

        // If no draft exists, create a new one.
        $newRequest = Repository::employee()->createEmployeeRequestDraft($employeeRequestData, legalEntity());
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        return $newRequest;
    }

    public function render(): View
    {
        return view('livewire.employee.employee');
    }

    protected function getEmployeeRequestForSave(): ?EmployeeRequest
    {
        if (!empty($this->employeeRequestId)) {
            return EmployeeRequest::find($this->employeeRequestId);
        }

        return null;
    }
}
