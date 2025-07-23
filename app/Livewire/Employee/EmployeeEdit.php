<?php
namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

#[AllowDynamicProperties]
class EmployeeEdit extends EmployeeComponent
{
    use Traits\ManagesEmployeeForm;

    public string $pageTitle;
    public ?EmployeeRequest $employeeRequest = null;
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, $id): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = true;

        // Спочатку знаходимо потрібну модель
        if (request()->routeIs('employee.*')) {
            $source = $legalEntity->employees()->findOrFail($id);
            $this->employee = $source;
        } else { // 'employee-request.*'
            $source = $legalEntity->employeeRequests()->findOrFail($id);
            $this->employeeRequest = $source;
        }

        // Тепер виконуємо спільні дії
        $this->authorize('update', $source);

        // Встановлюємо ID в обох випадках для консистентності
        $this->employeeRequestId = $source->id;

        $this->form->hydrate($source);
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'employee' => $this->employee ?? $this->employeeRequest
        ]);
    }
}
