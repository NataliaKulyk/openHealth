@php
    use App\Models\MedicalEvents\Sql\{DiagnosticReport, Encounter, Procedure};
    use App\Models\DeclarationRequest;
    use App\Models\Person\{Person, PersonRequest};
    use App\Enums\Person\VerificationStatus;
@endphp

<div>
    <section>
        <x-header-navigation x-data="{ showFilter: true }" class="breadcrumb-form">
            <x-slot name="title">{{ __('patients.patients') }}</x-slot>
            <x-slot name="navigation">

                <div class="justify-end block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700 mb-8">
                    @can('create', PersonRequest::class)
                        <a href="{{ route('patient.create', [legalEntity()]) }}" class="button-primary flex items-center gap-2">
                            @icon('plus', 'w-4 h-4')
                            {{ __('patients.add_patient') }}
                        </a>
                    @endcan
                </div>

                <div class="mb-8 flex items-center gap-1 font-semibold text-gray-900 dark:text-white">
                    @icon('search-outline', 'w-4.5 h-4.5')
                    <p>{{ __('patients.patient_search') }}</p>
                </div>

                @include('livewire.patient.parts.search-filter', ['context' => 'index'])

                <div class="mb-9 mt-6 flex gap-2">
                    @can('viewAny', Person::class)
                        <button wire:click.prevent="searchForPerson" class="flex items-center gap-2 button-primary">
                            @icon('search', 'w-4 h-4')
                            <span>{{ __('patients.search') }}</span>
                        </button>
                    @endcan
                    <button type="button" wire:click="resetFilters" class="button-primary-outline-red">
                        {{ __('forms.reset_all_filters') }}
                    </button>
                </div>
            </x-slot>
        </x-header-navigation>

        <div class="space-y-6" wire:key="patients-{{ $paginatedPatients->total() }}">
            @forelse($paginatedPatients->items() as $patient)
                <div wire:key="patient-{{ $patient['id'] }}"
                     class="bg-white shift-content dark:bg-gray-800 rounded-lg shadow p-6 lg:w-[1150px] lg:mx-0 lg:ml-3.5"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $patient['last_name'] }} {{ $patient['first_name'] }} {{ $patient['second_name'] ?? '' }}
                            </h3>
                            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                @if(isset($patient['phones'][0]['number']))
                                    <span class="flex items-center gap-1.5">
                                        @icon('tabler-phone', 'w-6 h-6 text-gray-800 dark:text-white')
                                        <span>{{ $patient['phones'][0]['number'] }}</span>
                                    </span>
                                @endif
                                @if($patient['birth_date'])
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                             viewBox="0 0 24 24">
                                           <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                                 d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H8z"/>
                                           <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                                 d="M16 2v4M8 2v4M3 10h18"/>
                                        </svg>
                                        <span>{{ $patient['birth_date'] }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            @if($patient['status'] === 'APPLICATION')
                                <a href="{{ route('patient.edit', [legalEntity(), 'id' => $patient['id']]) }}"
                                   class="item-add text-blue-600 hover:text-blue-800 flex items-center gap-1"
                                >
                                    <span class="text-xl leading-none">+</span>
                                    <span>{{ __('patients.continue_registration') }}</span>
                                </a>
                            @else
                                @can('view', Person::class)
                                    <button wire:click="redirectTo('{{ $patient['id'] }}', 'patient.patient-data')"
                                            class="cursor-pointer text-blue-600 hover:text-blue-800 flex items-center gap-1"
                                    >
                                        @icon('file-lines', 'w-4 h-4 text-blue-600 hover:text-blue-800')
                                        <span class="text-sm">{{ __('patients.view_record') }}</span>
                                    </button>
                                @endcan
                                @can('create', Encounter::class)
                                    <button wire:click="redirectTo('{{ $patient['id'] }}', 'encounter.create')"
                                            class="item-add text-green-600 hover:text-green-800 flex items-center gap-1"
                                    >
                                        <span>{{ __('patients.start_interacting') }}</span>
                                    </button>
                                @endcan
                            @endif
                        </div>
                    </div>
                    <div class="flow-root mt-4">
                        <div class="max-w-screen-xl">
                            <table class="table-input w-full table-auto">
                                <thead class="thead-input">
                                <tr>
                                    <th scope="col" class="th-input">{{ __('forms.phone') }}</th>
                                    <th scope="col" class="th-input">{{ __('forms.birth_date') }}</th>
                                    <th scope="col" class="th-input">{{ __('forms.rnokpp') }}</th>
                                    <th scope="col" class="th-input">{{ __('patients.birth_certificate') }}</th>
                                    <th scope="col" class="th-input">{{ __('forms.status.label') }}</th>
                                    <th scope="col" class="th-input text-center">{{ __('forms.actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top">
                                        {{ isset($patient['phones'][0]['number']) ? $patient['phones'][0]['number'] : '-' }}
                                    </td>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top">
                                        {{ $patient['birth_date'] }}
                                    </td>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top">
                                        {{ $patient['tax_id'] ?? '-' }}
                                    </td>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top">
                                        {{ $patient['birth_certificate'] ?? '-' }}
                                    </td>

                                    <td class="td-input whitespace-nowrap align-top">
                                        @php
                                            $statusEnum = VerificationStatus::tryFrom($patient['status']);

                                            if ($statusEnum) {
                                                $badgeClass = match ($statusEnum) {
                                                    VerificationStatus::VERIFIED, VerificationStatus::VERIFICATION_NOT_NEEDED => 'badge-green',
                                                    VerificationStatus::IN_REVIEW, VerificationStatus::VERIFICATION_NEEDED => 'badge-yellow',
                                                    VerificationStatus::NOT_VERIFIED, VerificationStatus::CHANGES_NEEDED => 'badge-red',
                                                };
                                            } else {
                                                $badgeClass = match ($patient['status']) {
                                                    'APPLICATION' => 'badge-purple',
                                                    'eHEALTH' => 'badge-green',
                                                    default => 'badge-gray'
                                                };
                                            }

                                            $label = $statusEnum?->label() ?? match ($patient['status']) {
                                                'APPLICATION' => 'ЗАЯВКА',
                                                'eHEALTH' => 'ЕСОЗ',
                                                default => $patient['status']
                                            };
                                        @endphp

                                        <span class="{{ $badgeClass }}">
                                            {{ $label }}
                                        </span>
                                    </td>

                                    <td class="td-input text-center">
                                        <div class="relative"
                                             x-data="{ openDropdown: false }"
                                             @click.outside="openDropdown = false"
                                        >
                                            <button @click="openDropdown = !openDropdown"
                                                    type="button"
                                                    class="cursor-pointer"
                                            >
                                                @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-gray-200')
                                            </button>

                                            <div x-show="openDropdown"
                                                 x-transition
                                                 class="absolute right-0 z-10 w-64 bg-white rounded shadow dark:bg-gray-700 whitespace-nowrap"
                                                 style="display: none;"
                                            >
                                                @if($patient['status'] === 'APPLICATION')
                                                    <div class="py-1" @click="openDropdown = false">
                                                        <button wire:click="removeApplication({{ $patient['id'] }})"
                                                                class="dropdown-button !flex gap-2 text-sm text-red-600 hover:bg-red-50 w-full"
                                                                type="button"
                                                        >
                                                            @icon('delete', 'w-5 h-5 text-red-600')
                                                            {{ __('forms.delete') }}
                                                        </button>
                                                    </div>
                                                @else
                                                    <div class="py-1">
                                                        @can('create', DeclarationRequest::class)
                                                            <a wire:click="redirectTo('{{ $patient['id'] }}', 'declaration.create')"
                                                               class="dropdown-button !flex gap-2 border-b border-gray-100 dark:border-gray-600 w-full"
                                                               @click="openDropdown = false"
                                                            >
                                                                @icon('file-text', 'w-4 h-4')
                                                                {{ __('patients.sign_declaration') }}
                                                            </a>
                                                        @endcan

                                                        @can('create', DiagnosticReport::class)
                                                            <a wire:click="redirectTo('{{ $patient['id'] }}', 'diagnostic-report.create')"
                                                               class="dropdown-button !flex gap-2 border-b border-gray-100 dark:border-gray-600 w-full"
                                                               @click="openDropdown = false"
                                                            >
                                                                @icon('activity', 'w-4 h-4')
                                                                {{ __('patients.create_diagnostic_report') }}
                                                            </a>
                                                        @endcan

                                                        @can('create', Procedure::class)
                                                            <a wire:click="redirectTo('{{ $patient['id'] }}', 'procedure.create')"
                                                               class="dropdown-button !flex gap-2 w-full"
                                                               @click="openDropdown = false"
                                                            >
                                                                @icon('settings', 'w-4 h-4')
                                                                {{ __('patients.create_procedure') }}
                                                            </a>
                                                        @endcan
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <p class="text-gray-500 dark:text-gray-400 text-lg">{{ __('forms.nothing_found') }}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $paginatedPatients->links() }}
        </div>
    </section>

    <x-forms.loading/>
    <x-messages/>
</div>
