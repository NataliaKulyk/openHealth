<section>
    <x-section-navigation x-data="{ showFilter: true }" class="breadcrumb-form">
        <x-slot name="title">
            {{ $patientFullName }}
        </x-slot>

        <x-slot name="navigation">
            <div class="sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700 mb-8">
                <a href="{{ route('encounter.create', [legalEntity(), 'patientId' => $id]) }}"
                   class="flex items-center gap-2 button-sync"
                >
                    <svg width="16" height="16">
                        <use xlink:href="#svg-plus"></use>
                    </svg>
                    {{ __('patients.start_interacting') }}
                </a>
                <a href="{{ route('declaration.create', [legalEntity(), 'patientId' => $id]) }}"
                   class="flex items-center gap-2 button-minor"
                >
                    <svg class="text-gray-800 dark:text-white" width="16" height="16" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8.16634 1.16675H3.49967C3.19026 1.16675 2.89351 1.28966 2.67472 1.50846C2.45592 1.72725 2.33301 2.024 2.33301 2.33341V11.6667C2.33301 11.9762 2.45592 12.2729 2.67472 12.4917C2.89351 12.7105 3.19026 12.8334 3.49967 12.8334H10.4997C10.8091 12.8334 11.1058 12.7105 11.3246 12.4917C11.5434 12.2729 11.6663 11.9762 11.6663 11.6667V4.66675M8.16634 1.16675L11.6663 4.66675M8.16634 1.16675V4.66675H11.6663M9.33301 7.58341H4.66634M9.33301 9.91675H4.66634M5.83301 5.25008H4.66634" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ __('patients.sign_declaration') }}
                </a>
            </div>

            <nav x-data="{ currentPath: window.location.pathname }">
                {{-- Mobile version --}}
                <div class="sm:hidden">
                    <label for="tabs" class="sr-only"></label>
                    <select id="tabs"
                            x-model="currentPath"
                            @change="window.location.href = $event.target.value"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                        @php
                            $navItems = [
                                'patient-data' => 'patients.patient_data',
                                'summary' => 'patients.summary',
                                'episodes' => 'patients.episodes'
                            ];
                        @endphp

                        @foreach($navItems as $route => $translation)
                            <option value="{{ route('patient.' . $route, [legalEntity(), 'patientId' => $id]) }}"
                                    :selected="currentPath.includes('{{ $route }}')"
                            >
                                {{ __($translation) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Desktop version --}}
                <ul class="hidden text-sm font-medium text-center text-gray-500 rounded-lg shadow-sm sm:flex dark:divide-gray-700 dark:text-gray-400">
                    @foreach($navItems as $route => $translation)
                        <li class="w-full focus-within:z-10">
                            <a href="{{ route('patient.' . $route, [legalEntity(), 'patientId' => $id]) }}"
                               @click="currentPath = '{{ route('patient.' . $route, [legalEntity(), 'patientId' => $id]) }}'"
                               class="inline-block w-full p-4 border-gray-200 dark:border-gray-700 focus:ring-4 focus:ring-blue-300 focus:outline-none"
                               :class="currentPath.includes('{{ $route }}')
                                   ? 'text-gray-900 bg-gray-100 dark:bg-gray-700 dark:text-white'
                                   : 'bg-white hover:text-gray-700 hover:bg-gray-50 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700'"
                            >
                                {{ __($translation) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </x-slot>
    </x-section-navigation>

    {{ $slot }}
</section>
