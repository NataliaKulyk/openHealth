@php
    use App\Models\{Contract, Declaration, DeclarationRequest, HealthcareService, LegalEntity, Division, License, EmployeeRole, Equipment};
    use App\Models\Employee\{Employee, EmployeeRequest};
    use App\Models\Person\{Person, PersonRequest};
@endphp

<aside id="drawer-navigation"
       class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
       aria-label="Sidebar"
>

    <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
        <ul class="space-y-2">

            @if(Auth::user()->can('create', LegalEntity::class) || Auth::user()->can('limitedAction', LegalEntity::class)  || legalEntity())
                <li x-data="{ open: false }" class="space-y-2">
                    <button @click="open = !open"
                            type="button"
                            class="cursor-pointer flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                            aria-controls="dropdown-legal-entity"
                            :aria-expanded="open"
                    >
                        @icon('institution', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                        <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ __('forms.institution') }}</span>

                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                             xmlns="http://www.w3.org/2000/svg"
                             :class="{ 'rotate-180': open, 'rotate-0': !open }"
                        >
                            <path fill-rule="evenodd"
                                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                  clip-rule="evenodd"
                            ></path>
                        </svg>
                    </button>

                    <ul id="dropdown-legal-entity"
                        x-cloak
                        class="py-2 space-y-2"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                    >
                        @if(legalEntity())
                            @can('viewAny', legalEntity())
                                <li>
                                    <a href="{{ route('legal-entity.details', [legalEntity()]) }}"
                                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    >
                                        @icon('details', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                        <span class="ml-3">{{ __('forms.details') }}</span>
                                    </a>
                                </li>
                            @endcan
                        @endif

                        @if (legalEntity()?->type->name !== LegalEntity::TYPE_MSP_LIMITED)
                            @if(legalEntity())
                                @can('edit', [LegalEntity::class, legalEntity()])
                                    <li>
                                        <a href="{{ route('legal-entity.edit', [legalEntity()]) }}"
                                        class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                        >
                                            @icon('edit2', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                            <span class="ml-3">{{ __('forms.edit') }}</span>
                                        </a>
                                    </li>
                                @endcan
                            @endif

                            @canany(['create', 'limitedAction'], LegalEntity::class)
                                <li>
                                    <a href="{{ legalEntity()
                                        ? route('legal-entity.create', [legalEntity()->id])
                                        : route('legal-entity.new.create') }}"
                                    class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    >
                                        @icon('create', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                        <span class="ml-3">{{ __('forms.create_legal_entity') }}</span>
                                    </a>
                                </li>
                            @endcanany
                        @endif
                    </ul>
                </li>
            @endif

            @if(legalEntity())
                @can('viewAny', Division::class)
                    <li>
                        <a href="{{ route('division.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('divisions', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('forms.divisions') }}</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', HealthcareService::class)
                    <li>
                        <a href="{{ route('healthcare-service.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('settings', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('forms.services') }}</span>
                        </a>
                    </li>
                @endcan

                    @if(Auth::user()->can('viewAny', Employee::class) || Auth::user()->can('viewAny', EmployeeRequest::class))
                        <li x-data="{ open: {{ (request()->routeIs('employee.*') || request()->routeIs('party.verification.*')) ? 'true' : 'false' }} }" class="space-y-2">
                            <button @click="open = !open"
                                    type="button"
                                    class="cursor-pointer flex items-center p-2 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    aria-controls="dropdown-employees"
                                    :aria-expanded="open"
                            >
                                @icon('employees', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                <span class="flex-1 ml-3 text-left whitespace-nowrap">{{ __('forms.employees') }}</span>

                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"
                                     xmlns="http://www.w3.org/2000/svg"
                                     :class="{ 'rotate-180': open, 'rotate-0': !open }"
                                >
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </button>

                            <ul id="dropdown-employees"
                                x-cloak
                                class="py-2 space-y-2"
                                x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                            >
                                <li>
                                    <a href="{{ route('employee.index', [legalEntity()]) }}"
                                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    >
                                        @icon('positions', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                        <span class="ml-3">{{ __('forms.positions') }}</span>
                                    </a>
                                </li>

                                    <li>
                                        <a href="{{ route('employee-role.index', [legalEntity()]) }}"
                                           class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                        >
                                            @icon('users-roles', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                            <span class="ml-3">{{ __('employee-roles.label') }}</span>
                                        </a>
                                    </li>

                                <li>
                                    <a href="{{ route('party.verification.index', [legalEntity()]) }}"
                                       class="flex items-center p-2 pl-11 w-full text-base font-medium text-gray-900 rounded-lg transition duration-75 group hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700"
                                    >
                                        @icon('verifications', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                                        <span class="ml-3">{{ __('forms.verifications') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                @can('viewAny', Contract::class)
                    <li>
                        <a href="{{ route('contract.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('contracts', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('forms.contracts') }}</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', License::class)
                    <li>
                        <a href="{{ route('license.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('licenses', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('forms.licenses') }}</span>
                        </a>
                    </li>
                @endcan

                @if(Auth::user()->can('viewAny', Declaration::class) || Auth::user()->can('viewAny', DeclarationRequest::class))
                    <li>
                        <a href="{{ route('declaration.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('declaration', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('forms.declarations') }}</span>
                        </a>
                    </li>
                @endif

                @if(Auth::user()->can('viewAny', Person::class) || Auth::user()->can('viewAny', PersonRequest::class))
                    <li>
                        <a href="{{ route('persons.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('patients', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('patients.patients') }}</span>
                        </a>
                    </li>
                @endif

                @can('viewAny', Equipment::class)
                    <li>
                        <a href="{{ route('equipment.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('equipment', 'w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white')
                            <span class="ml-3">{{ __('equipments.label') }}</span>
                        </a>
                    </li>
                @endcan
            @endif
        </ul>
    </div>
</aside>
