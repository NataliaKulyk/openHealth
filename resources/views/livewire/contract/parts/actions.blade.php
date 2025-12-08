@php
    $user = auth()->user();
    // Check permissions
    $canView = $user->can('view', $contract);
    // Assuming you might have 'update' policy later
    // $canEdit = $user->can('update', $contract);

    $hasActions = $canView;
@endphp

@if ($hasActions)
    <div class="relative flex justify-center" x-data="{ open: false }" @click.outside="open = false">
        <button
            @click="open = !open"
            class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white"
            type="button">
            {{-- Using the same icon style as Employees --}}
            @icon('edit-user-outline', 'svg-hover-action w-6 h-6 text-gray-800 dark:text-white')
        </button>

        <div x-show="open" x-transition
             style="display: none;"
             class="absolute right-0 top-full mt-1 z-50 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600 border border-gray-200 dark:border-gray-600">

            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">

                {{-- VIEW ACTION --}}
                @if($canView)
                    <li>
                        <a href="{{ route('contract.show', ['legalEntity' => legalEntity(), 'contract' => $contract->id]) }}"
                           class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                            @icon('eye', 'w-5 h-5 text-gray-500 dark:text-gray-300')
                            <span>{{ __('forms.view') }}</span>
                        </a>
                    </li>
                @endif

                {{-- EDIT ACTION (Only for Drafts/New) --}}
                {{-- Example logic: Show edit only if status is NEW --}}
                @if($contract->status->value === 'NEW')
                    <li>
                        <a href="#"
                           class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                            @icon('edit', 'w-5 h-5 text-gray-500 dark:text-gray-300')
                            <span>{{ __('forms.edit') }}</span>
                        </a>
                    </li>

                    <li>
                        <a href="#"
                           class="flex items-center gap-2 py-2 px-5 hover:bg-gray-100 dark:hover:bg-gray-600 text-red-600 dark:text-red-400">
                            @icon('trash', 'w-5 h-5 text-red-600 dark:text-red-400')
                            <span>{{ __('forms.delete') }}</span>
                        </a>
                    </li>
                @endif

            </ul>
        </div>
    </div>
@endif
