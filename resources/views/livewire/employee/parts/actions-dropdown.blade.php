@php
    $user = auth()->user();

    // Check if we are currently on the employee index page
    $isEmployeeIndex = request()->routeIs('employee.index');

    $isEmployee = $position instanceof \App\Models\Employee\Employee;
    $isRequest = $position instanceof \App\Models\Employee\EmployeeRequest;
    $status = $position->status?->value ?? null;

    // Permissions for Employees
    $canViewEmp = $isEmployee && $user->can('view', $position);
    $canUpdateEmp = $isEmployee && $user->can('update', $position);
    $canDeactivateEmp = $isEmployee && $status === 'APPROVED' && $user->can('deactivate', $position);
    $canSyncEmp = $isEmployee && $user->can('sync', $position);

    // Permissions for Requests
    $canViewReq = $isRequest && $user->can('view', $position);
    $canUpdateReq = $isRequest && $status === 'NEW' && $user->can('update', $position);
    $canDeleteReq = $isRequest && $status === 'NEW' && $user->can('delete', $position);

    // Updated sync logic: Show sync for requests ONLY if NOT on the employee index page
    $canSyncReq = $isRequest &&
                  !$isEmployeeIndex &&
                  in_array($status, ['NEW', 'SIGNED', 'APPROVED']);

    // Final check: if ANY action is available
    $hasActions = $canViewEmp || $canUpdateEmp || $canDeactivateEmp || $canSyncEmp ||
                  $canViewReq || $canUpdateReq || $canDeleteReq || $canSyncReq;
@endphp

@if ($hasActions)
    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
        <button
            @click="open = !open"
            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white"
            type="button">
            @icon('edit-user-outline', 'svg-hover-action w-6 h-6 text-gray-800 dark:text-white')
        </button>

        <div x-show="open"
             x-transition
             class="absolute right-0 z-50 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600"
             style="display: none;">

            @if($isEmployee)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @if($canSyncEmp)
                        <li>
                            <button type="button" wire:click="syncOne({{ $position->id }})" class="flex w-full items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('refresh', 'w-5 h-5 text-gray-500') {{ __('general.sync') }}
                            </button>
                        </li>
                    @endif

                    @if($canViewEmp)
                        <li>
                            <a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'employee' => $position]) }}"
                               class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('eye', 'w-5 h-5 text-gray-500') {{ __('forms.view') }}
                            </a>
                        </li>
                    @endif

                    @if($canUpdateEmp)
                        <li>
                            <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employee' => $position]) }}"
                               class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('pencil', 'w-5 h-5 text-gray-500') {{ __('forms.edit') }}
                            </a>
                        </li>
                    @endif
                </ul>

                @if($canDeactivateEmp)
                    <div class="py-1" @click="open = false">
                        <button type="button" wire:click="showModalDeactivate({{ $position->id }})"
                                class="flex items-center gap-2 w-full py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                            @icon('trash', 'w-5 h-5 text-red-600') {{ __('forms.dismiss') }}
                        </button>
                    </div>
                @endif

            @elseif($isRequest)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @if($canSyncReq)
                        <li>
                            <button type="button" wire:click="syncOne({{ $position->id }})" class="flex w-full items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('refresh', 'w-5 h-5 text-gray-500') {{ __('general.sync') }}
                            </button>
                        </li>
                    @endif

                    @if($canViewReq)
                        <li>
                            <a href="{{ route('employee-request.show', ['legalEntity' => legalEntity()->id, 'employee_request' => $position->id]) }}"
                               class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('eye', 'w-5 h-5 text-gray-500') {{ __('forms.view') }}
                            </a>
                        </li>
                    @endif

                    @if($canUpdateReq)
                        <li>
                            <a href="{{ route('employee-request.edit', ['legalEntity' => legalEntity()->id, 'employee_request' => $position->id]) }}"
                               class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600">
                                @icon('pencil', 'w-5 h-5 text-gray-500') {{ __('forms.edit') }}
                            </a>
                        </li>
                    @endif

                    @if($canDeleteReq)
                        <li>
                            <button type="button" wire:click="confirmRequestDeletion({{ $position->id }})"
                                    class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-red-600 w-full text-left">
                                @icon('trash', 'w-5 h-5 text-red-600') {{ __('forms.delete') }}
                            </button>
                        </li>
                    @endif
                </ul>
            @endif
        </div>
    </div>
@endif
