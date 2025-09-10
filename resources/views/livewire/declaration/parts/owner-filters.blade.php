<div class="form-row-3">
    {{-- Filter by declaration status --}}
    <div class="form-group group"
         x-data="{ open: false, selectedStatuses: $wire.entangle('statusFilter').live }"
    >
        <label for="statusFilter" class="label mb-1">{{ __('declarations.show') }}</label>
        <div class="relative">
            <input type="text"
                   id="statusFilter"
                   class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                   @click="open = !open"
                   :value="selectedStatuses.length ? selectedStatuses.map(status => status === 'active' ? 'Активні декларації' : (status === 'CANCELLED' ? 'Відмінені декларації' : status)).join(', ') : ''"
                   readonly
            />
            @icon('chevron-down', 'w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none')
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg"
            >
                <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                    <li>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input wire:model.live="statusFilter"
                                   type="checkbox"
                                   value="active"
                                   class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent"
                            />
                            <span>{{ __('declarations.active') }}</span>
                        </label>
                    </li>
                    <li>
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input wire:model.live="statusFilter"
                                   type="checkbox"
                                   value="CANCELLED"
                                   class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent"
                            />
                            <span>{{ __('declarations.cancelled') }}</span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Search by declaration number --}}
    <div class="form-group group">
        <input type="text"
               id="searchByNumber"
               placeholder=" "
               class="input peer"
               wire:model.live.debounce.300ms="searchByNumber"
               autocomplete="off"
        />
        <label for="search" class="label">
            {{ __('declarations.number') }}
        </label>
    </div>
</div>

{{-- Filter by doctor --}}
<div class="form-row-3 form-group group"
     x-data="{
        openDoctor: false,
        selectedDoctors: $wire.entangle('doctorFilter').live,
        doctors: @js($this->doctors->toArray()),
        getSelectedDoctorNames() {
            return this.doctors
                .filter(doctor => this.selectedDoctors.includes(doctor.uuid))
                .map(doctor => doctor.full_name)
                .join(', ');
    }
}">
    <label for="doctorFilter" class="label mb-1">{{ __('employees.doctor') }}</label>
    <div class="relative">
        <input type="text"
               id="doctorFilter"
               class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
               @click="openDoctor = !openDoctor"
               :value="selectedDoctors.length ? getSelectedDoctorNames() : 'ПІБ Лікаря'"
               readonly
        />
        @icon('chevron-down', 'w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none')

        <div x-show="openDoctor"
             @click.away="openDoctor = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto"
        >
            <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                <template x-for="doctor in doctors" :key="doctor.uuid">
                    <li>
                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-600 p-1 rounded">
                            <input wire:model.live="doctorFilter"
                                   type="checkbox"
                                   :value="doctor.uuid"
                                   class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent"
                            />
                            <span x-text="doctor.full_name"></span>
                        </label>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
