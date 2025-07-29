@php
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
    $tableHeaders = [
        __('forms.full_name'),
        __('forms.phone'),
        __('Д.Н.'),
        __('forms.rnokpp') . '(' . __('forms.ipn') . ')',
        __('forms.birth_certificate'),
        __('forms.status'),
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
            </x-slot>
        </x-section-navigation>

        @if($paginatedPatients && count($paginatedPatients) > 0)
            <div class="table-section"
                 x-data="{
                         activeFilter: 'all',
                         patients: {{ json_encode($paginatedPatients->items()) }},

                         filteredPatients() {
                             if (this.activeFilter === 'all') return this.patients;
                             return this.patients.filter(patient => patient.status === this.activeFilter);
                         },

                         init() {
                              Livewire.on('patientsUpdated', (updatedPatients) => {
                                  this.patients = updatedPatients[0];
                              });

                             Livewire.on('patientRemoved', (id) => {
                                 this.patients = this.patients.filter(patient => patient.id !== id[0]);
                             });
                         }
                     }"
            >
                <div class="mb-6 flex items-center gap-8">
                    <button @click="activeFilter = 'all'"
                            :class="activeFilter === 'all' ? 'button-primary' : 'button-minor'"
                    >
                        {{ __('patients.all') }}
                    </button>
                    <button @click="activeFilter = 'eHEALTH'"
                            :class="activeFilter === 'eHEALTH' ? 'button-primary' : 'button-minor'"
                    >
                        {{ __('patients.patients') }}
                    </button>
                    <button @click="activeFilter = 'APPLICATION'"
                            :class="activeFilter === 'APPLICATION' ? 'button-primary' : 'button-minor'"
                    >
                        {{ __('patients.applications') }}
                    </button>
                </div>
                <div class="table-container">
                    <div class="overflow-visible">
                        <table class="table-base">
                            <thead class="table-header">
                            <tr>
                                @foreach($tableHeaders as $tableHeader)
                                    <th wire:key="{{ $loop->index }}" scope="col" class="px-4 py-3">
                                        {{ $tableHeader }}
                                    </th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            <template x-for="patient in filteredPatients" :key="patient.id">
                                <tr class="border-b dark:border-gray-700">
                                    <th scope="row" class="table-cell-primary">
                                        <div class="text-base"
                                             x-text="`${ patient.last_name } ${ patient.first_name } ${ patient.second_name || '' }`"
                                        ></div>
                                        <template x-if="patient.status === 'APPLICATION'">
                                            <div class="flex gap-2 mt-2">
                                                <a :href="`{{ route('patient.form', [legalEntity(), 'id' => '']) }}/${patient.id}`"
                                                   class="button-primary"
                                                >
                                                    {{ __('patients.continue_registration') }}
                                                </a>
                                            </div>
                                        </template>
                                        <template x-if="patient.status !== 'APPLICATION'">
                                            <div class="flex gap-2 mt-2">
                                                <button @click.prevent="$wire.redirectToRecord(patient)"
                                                        type="button"
                                                        class="button-primary"
                                                >
                                                    {{ __('patients.view_record') }}
                                                </button>
                                                <button @click.prevent="$wire.redirectToEncounter(patient)"
                                                        class="button-sync flex items-center gap-2"
                                                >
                                                    {{ __('patients.start_interacting') }}
                                                </button>
                                            </div>
                                        </template>
                                    </th>
                                    <td class="td-input" x-text="patient.phones?.[0]?.number || '-'"></td>
                                    <td class="td-input" x-text="patient.birth_date"></td>
                                    <td class="td-input" x-text="patient.tax_id || '-'"></td>
                                    <td class="td-input" x-text="patient.birth_certificate || '-'"></td>
                                    <td class="td-input">
                                        <span x-text="
                                                  patient.status === 'APPLICATION' ? 'ЗАЯВКА' :
                                                  patient.status === 'eHEALTH' ? 'ЕСОЗ' :
                                                  patient.status === 'IN_REVIEW' ? 'ОБРОБЛЯЄТЬСЯ' :
                                                  patient.status
                                              "
                                              :class="{
                                                  'badge-purple': patient.status === 'APPLICATION',
                                                  'badge-green': patient.status === 'eHEALTH',
                                                  'badge-yellow': patient.status === 'IN_REVIEW'
                                              }"
                                        ></span>
                                    </td>
                                    <td class="td-input relative">
                                        {{-- That all that is needed for the dropdown --}}
                                        <div x-data="{
                                                 openDropdown: false,
                                                 toggle() {
                                                     if (this.openDropdown) {
                                                         return this.close()
                                                     }

                                                     this.$refs.button.focus()

                                                     this.openDropdown = true
                                                 },
                                                 close(focusAfter) {
                                                     if (!this.openDropdown) return

                                                     this.openDropdown = false

                                                     focusAfter && focusAfter.focus()
                                                 }
                                             }"
                                             @keydown.escape.prevent.stop="close($refs.button)"
                                             @focusin.window="! $refs.panel.contains($event.target) && close()"
                                             x-id="['dropdown-button']"
                                             class="relative"
                                        >
                                            {{-- Dropdown Button --}}
                                            <button x-ref="button"
                                                    @click="toggle()"
                                                    :aria-expanded="openDropdown"
                                                    :aria-controls="$id('dropdown-button')"
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

                                            {{-- Dropdown Panel --}}
                                            <div class="absolute right-[148px]">
                                                <div x-ref="panel"
                                                     x-show="openDropdown"
                                                     x-transition.origin.top.left
                                                     @click.outside="close($refs.button)"
                                                     :id="$id('dropdown-button')"
                                                     x-cloak
                                                     class="dropdown-panel relative w-[170px]"
                                                >
                                                    <button x-show="patient.status === 'APPLICATION'"
                                                            @click.prevent="$wire.removeApplication(patient.id)"
                                                            class="dropdown-button !flex gap-2"
                                                    >
                                                        <svg width="14" height="14">
                                                            <use xlink:href="#svg-trash"></use>
                                                        </svg>
                                                        {{ __('forms.delete') }}
                                                    </button>

                                                    <button x-show="patient.status !== 'APPLICATION'"
                                                            @click.prevent="$wire.createDiagnosticReport(patient.id)"
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
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            </tbody>
                        </table>
                    </div>
                    <nav class="table-nav" aria-label="Table navigation">
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            {{ __('Показано') }}
                            <span class="table-nav-number">
                                {{ $paginatedPatients->firstItem() }}-{{ $paginatedPatients->lastItem() }}
                            </span>
                            {{ __('з') }}
                            <span class="table-nav-number">
                                {{ $paginatedPatients->total() }}
                            </span>
                        </span>
                        <ul class="pagination-list">
                            {{-- Previous page --}}
                            <li>
                                <a href="{{ $paginatedPatients->previousPageUrl() }}"
                                   class="pagination-prev-button">
                                    <span class="sr-only">{{ __('forms.previous') }}</span>
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                              d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                              clip-rule="evenodd"
                                        />
                                    </svg>
                                </a>
                            </li>
                            {{-- Page numbers --}}
                            @foreach ($paginatedPatients->getUrlRange(max(1, $paginatedPatients->currentPage() - 2), min($paginatedPatients->lastPage(), $paginatedPatients->currentPage() + 2)) as $page => $url)
                                <li>
                                    <a href="{{ 'patient' . $url }}"
                                       {{ $paginatedPatients->currentPage() === $page ? 'aria-current="page"' : '' }}
                                       class="pagination-number {{ $paginatedPatients->currentPage() === $page ? 'pagination-number-active' : 'pagination-number-inactive' }}"
                                    >
                                        {{ $page }}
                                    </a>
                                </li>
                            @endforeach
                            <li>
                                <a href="{{ $paginatedPatients->nextPageUrl() }}"
                                   class="pagination-next-button">
                                    <span class="sr-only">{{ __('forms.next') }}</span>
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewbox="0 0 20 20"
                                         xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd"
                                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                              clip-rule="evenodd"
                                        />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        @elseif($searchPerformed && $paginatedPatients->isEmpty())
            <div class="rounded-lg p-4 bg-gray-200 dark:bg-gray-900">
                <div class="flex items-center gap-2">
                    <svg width="20" height="20">
                        <use xlink:href="#svg-exclamation-circle"></use>
                    </svg>
                    <p class="default-p font-semibold">{{ __('patients.nobody_found') }}</p>
                </div>
                <p class="default-p">{{ __('patients.try_change_search_parameters') }}</p>
            </div>
        @endif
    </section>

    <x-forms.loading/>
</div>
