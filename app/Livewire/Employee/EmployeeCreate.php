<?php

namespace App\Livewire\Employee;

use App\Models\Employee\EmployeeRequest;
use App\Repositories\EmployeeRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException;

class EmployeeCreate extends EmployeeComponent
{
    use WithFileUploads;

    public Forms\EmployeeForm $form;

    /**
     * The currently active EmployeeRequest draft.
     * @var EmployeeRequest|null
     */
    public ?EmployeeRequest $EmployeeRequest = null;

    /**
     * Property to control the visibility of the KEP signature block.
     *
     * @var bool
     */
    public bool $showSignatureBlock = false;

    /**
     * Bootstrap the component, injecting dependencies.
     * @param EmployeeRepository $employeeRepository
     * @return void
     */
    public function boot(EmployeeRepository $employeeRepository): void
    {
        parent::boot($employeeRepository);
    }

    public function save(): void
    {
        try {
            $this->form->validate();
            $preparedData = $this->form->getPreparedData();
            $preparedData['legal_entity_uuid'] = legalEntity()->uuid;
            $preparedData['legal_entity_id'] = legalEntity()->id;

            app(EmployeeRepository::class)->saveEmployeeData(
                $preparedData,
                legalEntity(),
                null,
                null,
                true
            );

            session()->flash('success', __('Employee request saved successfully.'));
            $this->showSignatureBlock = true;
        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');

            Log::error(
                'Validation Error in EmployeeCreate::save(): ' . json_encode(
                    $e->errors(),
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                )
            );
            session()->flash('error', __('Validation failed. Please check the form.'));
            throw $e;
        } catch (Exception $e) {
            $this->dispatch('employee-form-failed');

            Log::error('Critical Error in EmployeeCreate::save(): ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            session()->flash('error', __('Failed to save employee. An unexpected error occurred.'));
            $this->showSignatureBlock = false;
        }
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(): View
    {
        $pageTitle = __('forms.add_employee');
        return view('livewire.employee.employee-create', compact('pageTitle'));
    }
}
