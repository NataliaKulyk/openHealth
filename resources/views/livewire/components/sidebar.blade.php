@php
    use App\Models\{Contract, Declaration, DeclarationRequest, HealthcareService, LegalEntity, Division, License};
    use App\Models\Employee\{Employee, EmployeeRequest};
    use App\Models\Person\{Person, PersonRequest};
@endphp

<aside id="drawer-navigation"
       class="fixed top-0 left-0 z-40 w-64 h-screen pt-14 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
       aria-label="Sidebar"
>

    <div class="overflow-y-auto py-5 px-3 h-full bg-white dark:bg-gray-800">
        <ul class="space-y-2">
            @can('create', LegalEntity::class)
                <li>
                    <a href="{{ legalEntity()
                        ? route('legal-entity.create', [legalEntity()->id])
                        : route('legal-entity.new.create') }}"
                       class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                    >
                        @icon('create', 'w-4 h-4')
                        <span class="ml-3">{{ __('forms.create_legal_entity') }}</span>
                    </a>
                </li>
            @endcan

            @if(legalEntity())
                @can('edit', LegalEntity::class)
                    <li>
                        <a href="{{ route('legal-entity.edit', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            @icon('edit2', 'w-4 h-4')
                            <span class="ml-3">{{ __('forms.edit_legal_entity') }}</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', Division::class)
                    <li>
                        <a href="{{ route('division.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor"
                            >
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"
                                />
                            </svg>

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
                    <li>
                        <a href="{{ route('employee.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor"
                            >
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z"
                                />
                            </svg>

                            <span class="ml-3">{{ __('forms.employees') }}</span>
                        </a>
                    </li>
                @endif

                @can('viewAny', Contract::class)
                    <li>
                        <a href="{{ route('contract.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor"
                            >
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75"
                                />
                            </svg>

                            <span class="ml-3">{{ __('forms.contracts') }}</span>
                        </a>
                    </li>
                @endcan

                @can('viewAny', License::class)
                    <li>
                        <a href="{{ route('license.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor"
                            >
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
                                />
                            </svg>

                            <span class="ml-3">{{ __('forms.licenses') }}</span>
                        </a>
                    </li>
                @endcan

                @if(Auth::user()->can('viewAny', Declaration::class) || Auth::user()->can('viewAny', DeclarationRequest::class))
                    <li>
                        <a href="{{ route('declaration.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.5"
                                 stroke="currentColor"
                            >
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"
                                />
                            </svg>

                            <span class="ml-3">{{ __('forms.declarations') }}</span>
                        </a>
                    </li>
                @endif

                @if(Auth::user()->can('viewAny', Person::class) || Auth::user()->can('viewAny', PersonRequest::class))
                    <li>
                        <a href="{{ route('patient.index', [legalEntity()]) }}"
                           class="flex items-center p-2 text-base font-medium text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                        >
                            <svg aria-hidden="true"
                                 class="w-6 h-6 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white"
                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                 viewBox="0 0 24 24"
                            >
                                <path stroke="currentColor"
                                      stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M15 9h3m-3 3h3m-3 3h3m-6 1c-.306-.613-.933-1-1.618-1H7.618c-.685 0-1.312.387-1.618 1M4 5h16a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm7 5a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"
                                />
                            </svg>

                            <span class="ml-3">{{ __('patients.patients') }}</span>
                        </a>
                    </li>
                @endif
            @endif
        </ul>
    </div>
</aside>
