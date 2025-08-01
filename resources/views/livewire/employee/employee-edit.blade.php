<div>
    @php
        $pageTitle = __('forms.edit_employee');
    @endphp

    @include('livewire.employee.employee', [
        'pageTitle' => $pageTitle . ' ' . ($employee['fullName'] ?? '')
    ])
</div>
