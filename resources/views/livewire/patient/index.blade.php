@php
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
    $tableHeaders = [
        __('forms.full_name'),
        __('forms.phone'),
        __('Д.Н.'),
        __('forms.rnokpp') . '(' . __('forms.ipn') . ')',
        __('forms.birth_certificate'),
        __('forms.status.label'),
        __('forms.action')
    ];
@endphp

<div>
    <div aria-hidden="true" class="hidden">
        {!! $svgSprite !!}
    </div>

    <section>
        <x-section-navigation x-data="{ showFilter: true }" class="breadcrumb-form">
            <x-slot name="title">{{ __('patients.patients') }}</x-slot>
            <x-slot name="navigation">

                <div class="justify-end block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700 mb-8">
                    <div class="button-group flex gap-8">
                        <button type="button" class="button-primary">
                            <a href="{{ route('patient.form', [legalEntity()]) }}">
                                {{ __('patients.add_patient') }}
                            </a>
                        </button>
                        <button class="button-sync">
                            {{ __('forms.synchronise_with_eHealth') }}
                        </button>
                    </div>
                </div>

                <div class="mb-8 flex items-center gap-1 font-semibold text-gray-900 dark:text-white">
                    <svg width="18" height="18">
                        <use xlink:href="#svg-search-outline"></use>
                    </svg>
                    <p>{{ __('patients.patient_search') }}</p>
                </div>

                @include('livewire.patient.parts.search-filter')

                <div class="py-4">
                    <button wire:click.prevent="searchForPerson" class="flex items-center gap-2 button-primary">
                        <svg width="16" height="16">
                            <use xlink:href="#svg-search"></use>
                        </svg>
                        <span>{{ __('patients.search') }}</span>
                    </button>
                </div>

                <div class="mb-6 flex items-center gap-8">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" value="all" wire:model.live="activeFilter"
                               class="sr-only" />
                        <span class="{{ $activeFilter === 'all' ? 'button-primary' : 'button-minor' }}">
                            {{ __('patients.all') }}
                        </span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" value="eHEALTH" wire:model.live="activeFilter"
                               class="sr-only" />
                        <span class="{{ $activeFilter === 'eHEALTH' ? 'button-primary' : 'button-minor' }}">
                            {{ __('patients.patients') }}
                        </span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="radio" value="APPLICATION" wire:model.live="activeFilter"
                               class="sr-only" />
                        <span class="{{ $activeFilter === 'APPLICATION' ? 'button-primary' : 'button-minor' }}">
                            {{ __('patients.applications') }}
                        </span>
                    </label>
                    <button type="button" wire:click="resetFilters" class="button-primary">
                        Скинути всі фільтри
                    </button>
                </div>
            </x-slot>
        </x-section-navigation>

        <x-section>
            <div class="space-y-6">
                @forelse($paginatedPatients as $patient)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 lg:w-[1150px] lg:mx-0 lg:ml-10" wire:key="patient-{{ $patient['id'] }}">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $patient['last_name'] }} {{ $patient['first_name'] }} {{ $patient['second_name'] ?? '' }}
                                </h3>
                                <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                    @if(isset($patient['phones'][0]['number']))
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                                 viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M18.427 14.768 17.2 13.542a1.733 1.733 0 0 0-2.45 0l-.613.613a1.732 1.732 0 0 1-2.45 0l-1.838-1.84a1.735 1.735 0 0 1 0-2.452l.612-.613a1.735 1.735 0 0 0 0-2.452L9.237 5.572a1.6 1.6 0 0 0-2.45 0c-3.223 3.2-1.702 6.896 1.519 10.117 3.22 3.221 6.914 4.745 10.12 1.535a1.601 1.601 0 0 0 0-2.456Z"/>
                                            </svg>
                                            <span>{{ $patient['phones'][0]['number'] }}</span>
                                        </span>
                                    @endif
                                    @if($patient['birth_date'])
                                        <span class="flex items-center gap-1.5">
                                            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                               <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H8z"/>
                                               <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M16 2v4M8 2v4M3 10h18"/>
                                            </svg>
                                            <span>{{ $patient['birth_date'] }}</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                @if($patient['status'] === 'APPLICATION')
                                    <a href="{{ route('patient.form', [legalEntity(), 'id' => $patient['id']]) }}"
                                       class="item-add text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <span class="text-xl leading-none">+</span>
                                        <span>{{ __('patients.continue_registration') }}</span>
                                    </a>
                                @else
                                    <button wire:click="redirectToRecord({{ $patient['id'] }})"
                                            class="item-add text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <span>{{ __('patients.view_record') }}</span>
                                    </button>
                                    <button wire:click="redirectToEncounter({{ $patient['id'] }})"
                                            class="item-add text-green-600 hover:text-green-800 flex items-center gap-1">
                                        <span>{{ __('patients.start_interacting') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="flow-root mt-4">
                            <div class="max-w-screen-xl">
                                <table class="table-input w-full table-fixed">
                                    <thead class="thead-input">
                                    <tr>
                                        <th scope="col" class="th-input w-[20%]">Телефон</th>
                                        <th scope="col" class="th-input w-[15%]">Дата народження</th>
                                        <th scope="col" class="th-input w-[20%]">РНОКПП</th>
                                        <th scope="col" class="th-input w-[20%]">Свідоцтво</th>
                                        <th scope="col" class="th-input w-[15%]">Статус</th>
                                        <th scope="col" class="th-input w-[10%] text-center">Дії</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td class="td-input break-words whitespace-normal align-top">
                                            {{ isset($patient['phones'][0]['number']) ? $patient['phones'][0]['number'] : '-' }}
                                        </td>
                                        <td class="td-input break-words whitespace-normal align-top">
                                            {{ $patient['birth_date'] }}
                                        </td>
                                        <td class="td-input break-words whitespace-normal align-top">
                                            {{ $patient['tax_id'] ?? '-' }}
                                        </td>
                                        <td class="td-input break-words whitespace-normal align-top">
                                            {{ $patient['birth_certificate'] ?? '-' }}
                                        </td>
                                        <td class="td-input break-words whitespace-normal align-top">
                                            @if($patient['status'] === 'APPLICATION')
                                                <span class="badge-purple">ЗАЯВКА</span>
                                            @elseif($patient['status'] === 'eHEALTH')
                                                <span class="badge-green">ЕСОЗ</span>
                                            @elseif($patient['status'] === 'IN_REVIEW')
                                                <span class="badge-yellow">ОБРОБЛЯЄТЬСЯ</span>
                                            @else
                                                <span>{{ $patient['status'] }}</span>
                                            @endif
                                        </td>
                                        <td class="td-input text-center">
                                            <div class="relative" x-data="{ openDropdown: false }" @click.outside="openDropdown = false">
                                                <button @click="openDropdown = !openDropdown"
                                                        type="button"
                                                        class="cursor-pointer"
                                                >
                                                    <svg class="w-6 h-6 text-gray-800 dark:text-gray-200" aria-hidden="true"
                                                         xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         fill="none"
                                                         viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="square"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                                    </svg>
                                                </button>

                                                <div x-show="openDropdown" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">
                                                    @if($patient['status'] === 'APPLICATION')
                                                        <div class="py-1" @click="openDropdown = false">
                                                            <button wire:click="removeApplication({{ $patient['id'] }})"
                                                                    class="dropdown-button !flex gap-2"
                                                            >
                                                                <svg width="14" height="14">
                                                                    <use xlink:href="#svg-trash"></use>
                                                                </svg>
                                                                {{ __('forms.delete') }}
                                                            </button>
                                                        </div>
                                                    @else
                                                        <div class="py-1" @click="openDropdown = false">
                                                            <button wire:click="createDiagnosticReport({{ $patient['id'] }})"
                                                                    class="dropdown-button !flex gap-2"
                                                            >
                                                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"
                                                                     xmlns="http://www.w3.org/2000/svg">
                                                                    <path
                                                                        d="M12.8337 7.5H10.5003L8.75033 12.75L5.25033 2.25L3.50033 7.5H1.16699"
                                                                        stroke="currentColor" stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                    />
                                                                </svg>
                                                                {{ __('patients.create_diagnostic_report') }}
                                                            </button>
                                                        </div>
                                                        <div class="py-1" @click="openDropdown = false">
                                                            <a href="{{ route('declaration.create', [legalEntity(), 'patientId' => $patient['id']]) }}"
                                                               class="dropdown-button !flex gap-2"
                                                            >
                                                                <svg class="text-gray-800 dark:text-white" width="16" height="16" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M8.16634 1.16675H3.49967C3.19026 1.16675 2.89351 1.28966 2.67472 1.50846C2.45592 1.72725 2.33301 2.024 2.33301 2.33341V11.6667C2.33301 11.9762 2.45592 12.2729 2.67472 12.4917C2.89351 12.7105 3.19026 12.8334 3.49967 12.8334H10.4997C10.8091 12.8334 11.1058 12.7105 11.3246 12.4917C11.5434 12.2729 11.6663 11.9762 11.6663 11.6667V4.66675M8.16634 1.16675L11.6663 4.66675M8.16634 1.16675V4.66675H11.6663M9.33301 7.58341H4.66634M9.33301 9.91675H4.66634M5.83301 5.25008H4.66634" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                                                                </svg>
                                                                {{ __('patients.sign_declaration') }}
                                                            </a>
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
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{__('Нічого не знайдено')}}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $paginatedPatients->links() }}
            </div>
        </x-section>
    </section>

    <x-forms.loading/>
</div>
