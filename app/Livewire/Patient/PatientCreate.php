<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Models\LegalEntity;
use Illuminate\View\View;

class PatientCreate extends PatientComponent
{
    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount();
    }

    public function render(): View
    {
        return view('livewire.patient.patient-create');
    }
}
