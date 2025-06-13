<body class="bg-white dark:bg-gray-800 min-h-screen text-gray-900 dark:text-white">
    <div>
        <x-section-navigation class="breadcrumb-form">
            <x-slot name="title">{{ __('Нова додаткова ліцензія') }}</x-slot>
        </x-section-navigation>
        <fieldset class="fieldset">
            <form class="form">
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="license_kind" id="license_kind" class="peer input dark:text-gray-400" value="Додаткова" placeholder=" " required />
                        <label for="license_kind" class="label">Вид ліцензії</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div x-data="{
                       open: false,
                       selected: '',
                       choose(option) {
                       this.selected = option;
                       this.open = false;
                    }
                        }" class="relative w-full">
                        <div class="input-select peer cursor-pointer whitespace-normal break-words min-h-[48px] px-3 py-2 pr-10"
                             x-on:click="open = !open"
                             :class="{ 'ring-1 ring-blue-500 border-blue-500': open }">
                            <span x-text="selected || 'Тип ліцензії'" class="block w-full text-sm leading-snug text-left pr-6"></span>
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <ul x-show="open" x-transition x-cloak class="dropdown-panel w-full max-h-60 overflow-auto z-10">
                            @foreach ($dictionaries['LICENSE_TYPE'] ?? [] as $key => $label)
                                <li>
                                    <button type="button"
                                            x-text="'{{ $label }}'"
                                            x-on:click="choose('{{ $label }}')"
                                            class="text-left text-sm whitespace-normal break-words px-3 py-2">
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <input type="hidden" name="licenseType" :value="selected">
                    </div>
                    <div class="form-group">
                        <input type="text" name="floating_order_number" id="floating_order_number" class="peer input" placeholder=" " required />
                        <label for="floating_order_number" class="label">Номер наказу</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="floating_issued_the_license" id="floating_issued_the_license" class="peer input" placeholder=" " required />
                        <label for="floating_issued_the_license" class="label">Ким видано</label>
                    </div>
                    <div class="form-group">
                        <input type="text" name="floating_licensed_activity" id="floating_licensed_activity" class="peer input" placeholder=" " required />
                        <label for="floating_licensed_activity" class="label">Напрям діяльності, що ліцензовано</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <input type="text" name="floating_license_series_number" id="floating_license_series_number" class="peer input" placeholder=" " required />
                        <label for="floating_license_series_number" class="label">Серія та/або номер ліцензії</label>
                    </div>
                    <div class="form-group datepicker-wrapper relative w-full">
                        <input datepicker datepicker-autohide type="text" name="floating_date_of_license_issuance" id="floating_date_of_license_issuance" class="peer input pl-10 appearance-none"  placeholder=" " required />
                        <label for="floating_date_of_license_issuance" class="wrapped-label">Дата видачі ліцензії</label>
                    </div>
                </div>
                <div class="form-row-3">
                    <div class="form-group datepicker-wrapper relative w-full">
                        <input datepicker datepicker-autohide type="text" name="floating_date_of_license_start_date" id="floating_date_of_license_start_date" class="peer input pl-10 appearance-none" placeholder=" " required />
                        <label for="floating_date_of_license_start_date" class="wrapped-label">Дата початку дії ліцензії</label>
                    </div>
                    <div class="form-group datepicker-wrapper relative w-full">
                        <input datepicker datepicker-autohide type="text" name="floating_date_of_license_expiry" id="floating_date_of_license_expiry" class="peer input pl-10 appearance-none" placeholder=" " required />
                        <label for="floating_date_of_license_expiry" class="wrapped-label">Дата завершення дії ліцензії</label>
                    </div>
                </div>
                <div class="flex justify-start gap-4 mt-10">
                    <button type="button" class="button-minor">
                        Скасувати
                    </button>
                    <button type="submit" class="button-primary">
                        Додати ліцензію
                    </button>
                </div>
            </form>
        </fieldset>
    </div>
    </div>
</body>
