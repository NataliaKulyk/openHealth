<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Models\DeclarationRequest;
use App\Models\LegalEntity;

class DeclarationEdit extends DeclarationComponent
{
    public function mount(LegalEntity $legalEntity, int $patientId, int $declarationRequestId): void
    {
        $this->baseMount($patientId);

        if (session('showSignModal')) {
            $this->showSignModal = true;
        }

        $declarationRequest = DeclarationRequest::select(['uuid', 'employee_id', 'authorize_with', 'data_to_be_signed'])
            ->with('employee')
            ->where('id', $declarationRequestId)
            ->first();

        if ($declarationRequest->data_to_be_signed) {
            $this->printableContent = $declarationRequest->data_to_be_signed['content'];
            $this->dataToBeSigned = $declarationRequest->data_to_be_signed;
        }

        // Set form data
        $this->form->employeeId = $declarationRequest->employee->uuid;
        $this->form->authorizeWith = $declarationRequest->authorizeWith;

        $this->declarationRequestUuid = $declarationRequest->uuid ?? '';
    }
}
