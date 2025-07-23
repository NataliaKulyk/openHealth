<div>
    @php
        $pageTitle = $employee instanceof \App\Models\Employee\EmployeeRequest
            ? __('forms.edit_employee_request')
            : __('forms.edit_employee');
    @endphp

    @include('livewire.employee.employee', ['pageTitle' => $pageTitle])
</div>
