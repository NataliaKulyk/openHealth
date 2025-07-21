@php
    $user = auth()->user();
    $hasActions = false;

if ($position instanceof \App\Models\Employee\Employee) {
        if (
            $user->can('view', $position) ||
            $user->can('update', $position) ||
            ($user->can('dismiss', $position) && $position->status?->value === \App\Enums\Status::APPROVED->value)
        ) {
            $hasActions = true;
        }
    } elseif ($position instanceof \App\Models\Employee\EmployeeRequest) {
        if (
            $user->can('view', $position) ||
            $user->can('update', $position) ||
            ($user->can('delete', $position) && !$position->uuid)
        ) {
            $hasActions = true;
        }
    }
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        @click="
            @if($hasActions)
                open = !open
            @else
                $wire.dispatch('flashMessage', { message: '{{ __('forms.no_actions_available') }}', type: 'error' })
            @endif
        "
        class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white" type="button">
        <svg class="w-6 h-6 text-gray-800 dark:text-white svg-hover-action" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
        </svg>
    </button>

    @if($hasActions)
        <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">

            {{-- Logic for Approved Employees --}}
            @if($position instanceof \App\Models\Employee\Employee)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @can('view', $position)
                        <li>
                            <a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'employee']) }}" class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                    <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                {{ __('forms.view') }}
                            </a>
                        </li>
                    @endcan
                    @can('update', $position)
                        <li>
                            <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'employee']) }}" class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                {{ __('forms.edit') }}
                            </a>
                        </li>
                    @endcan
                </ul>
                @if($position->status?->value === 'APPROVED' && $canDismissEmployee)
                    <div class="py-1" @click="open = false">
                        <button type="button" wire:click="showModalDismissed({{ $position->id }})" class="flex items-center gap-2 w-full py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                            <svg class="w-5 h-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            {{ __('forms.dismiss') }}
                        </button>
                    </div>
                @endif

                {{-- Logic for Employee Requests (Drafts) --}}
            @elseif($position instanceof \App\Models\Employee\EmployeeRequest)
                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                    @can('view', $position)
                        <li>
                            <a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'request']) }}" class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                    <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                                {{ __('forms.view') }}
                            </a>
                        </li>
                    @endcan
                    @can('update', $position)
                        <li>
                            <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'id' => $position->id, 'type' => 'request']) }}" class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                </svg>
                                {{ __('forms.edit') }}
                            </a>
                        </li>
                    @endcan
                </ul>
                @if(!$position->uuid && $canDeleteEmployeeRequest)
                    <div class="py-1" @click="open = false">
                        <button type="button" wire:click="confirmRequestDeletion({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                            {{ __('forms.delete') }}
                        </button>
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
