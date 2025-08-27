@php
    $readonly = $action === 'show';

    // Determine an appropriate HTTP-method
    $httpMethod = match ($action) {
        'show'   => 'GET',
        'update'   => 'PATCH',
        'store' => 'POST',
        default  => 'GET'
    };
@endphp

<div>
    <x-messages />

    <x-section-navigation x-data="{ showFilter: false }" class=''>
        <x-slot name='title'>
            @yield('title')
        </x-slot>

        <x-slot name="description">
            @yield('description')
        </x-slot>
    </x-section-navigation>

    <section class="section-form">
         <div class="form-row" x-data="{ isDisabled: @json($readonly) }">
            {{-- <form submit="{{ $action }}"> --}}
                <form submit="{{ $action }}">

                @if (!in_array(strtoupper($httpMethod), ['GET', 'POST']))
                    @method($httpMethod)
                @endif

                    <fieldset class="fieldset">
                        <legend class="legend">
                            <h2>{{__('forms.main_information')}}</h2>
                        </legend>
                        <div class="form">
                            <div class="form-row-3">
                                <div class="form-group">
                                    <input wire:model='divisionForm.division.name' type="text" x-bind:disabled="isDisabled" name="name_division" id="name" class="peer input" placeholder=" " required />
                                    <label for="name_division" class="label">{{ __('forms.full_name_division') }}</label>
                                    @error('divisionForm.division.name') <p class="text-error">{{$message}}</p> @enderror
                                </div>
                                <div class="form-group">
                                    <input wire:model='divisionForm.division.email' type="text" x-bind:disabled="isDisabled" name="email" id="email" class="peer input" placeholder=" " required />
                                    <label for="email" class="label">{{ __('forms.email') }}</label>
                                    @error('divisionForm.division.email') <p class="text-error">{{$message}}</p> @enderror
                                </div>
                            </div>
                            <div class="form-row-3">
                                <div class="form-group">
                                    <select wire:model='divisionForm.division.type'
                                            id='type'
                                            class='peer input'
                                            x-bind:disabled="{{ ($action === 'update' && $status !== 'DRAFT') || $action === 'show' ? 'true' : 'false' }}"
                                    >
                                        <option value="" disabled selected hidden>{{ __('forms.type') }} *</option>
                                        @foreach ($dictionaries['DIVISION_TYPE'] as $k => $type)
                                            <option value="{{ $k }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                    <label for="type" class="label">{{ __('forms.type') }} *</label>
                                    @error('divisionForm.division.type')
                                    <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <input wire:model='divisionForm.division.external_id' type="text" x-bind:disabled="{{ ($action === 'update' && $status !== 'DRAFT') || $action === 'show'? 'true' : 'false' }}" name="external_id" id="external_id" class="peer input" placeholder=" " />
                                    <label for="external_id" class="label">{{ __('forms.externalId') }}</label>
                                    @error('divisionForm.division.external_id') <p class="text-error">{{$message}}</p> @enderror
                                </div>
                            </div>

                            <div
                                class="space-y-2"
                                x-data="{ phones: $wire.entangle('divisionForm.division.phones') }"
                                x-init="if (!Array.isArray(phones) || phones.length === 0) { phones = [{ type: 'MOBILE', number: '' }] }"
                            >
                                <template x-for="(phone, index) in phones" :key="index">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                                        {{-- Phone Type Select --}}
                                        <div class="form-group">
                                            <select x-model="phone.type" class="input-select @error('divisionForm.division.phones.*.type') input-error @enderror" required x-bind:disabled="isDisabled">
                                                <option value="" disabled>{{__('forms.type_mobile')}} *</option>
                                                @foreach($dictionaries['PHONE_TYPE'] as $key => $phoneType)
                                                    <option value="{{$key}}">{{$phoneType}}</option>
                                                @endforeach
                                            </select>
                                            <label class="label">{{ __('forms.phone_type') }}</label>
                                            @error('divisionForm.division.phones.*.type') <p class="text-error">{{ $message }}</p> @enderror
                                        </div>

                                        {{-- Phone Number Input --}}
                                        <div class="form-group phone-wrapper">
                                            <input
                                                required
                                                type="tel"
                                                placeholder=" "
                                                class="peer input pl-10 with-leading-icon text-gray-500"
                                                x-model="phone.number"
                                                x-mask="+380999999999"
                                                x-bind:disabled="isDisabled"
                                            />
                                            <label class="wrapped-label">{{ __('forms.phone_number') }}</label>
                                            @error('divisionForm.division.phones.*.number') <p class="text-error">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="flex items-center space-x-4 justify-start">
                                            <template x-if="phones.length > 1">
                                                <button type="button" @click="phones.splice(index, 1)" class="item-remove text-red-600 hover:text-red-800 justify-self-start">
                                                    <span>{{__('forms.remove_phone')}}</span>
                                                </button>
                                            </template>
                                            <template x-if="index === phones.length - 1">
                                                <button type="button" @click="phones.push({ type: 'MOBILE', number: '' })" class="item-add">
                                                    <span>{{__('forms.add_phone')}}</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="form-row-3">
                                <div class="form-group">
                                    <input wire:model='divisionForm.division.location.longitude' type="text" x-bind:disabled="isDisabled" name="longitude" x-mask='99.999999' id="longitude" class="peer input" placeholder=" " />
                                    <label for="longitude" class="label">{{ __('forms.longitude') }}</label>
                                    @error('divisionForm.division.location.longitude') <p class="text-error">{{$message}}</p> @enderror
                                </div>
                                <div class="form-group">
                                    <input wire:model='divisionForm.division.location.latitude' type="text" x-bind:disabled="isDisabled" name="latitude" x-mask='99.999999' id="latitude" class="peer input" placeholder=" " />
                                    <label for="latitude" class="label">{{ __('forms.latitude') }}</label>
                                    @error('divisionForm.division.location.latitude') <p class="text-error">{{$message}}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    {{-- ADDRESS --}}

                    <fieldset class="fieldset">

                        <legend class="legend">
                            <h2>{{ __('forms.address') }}</h2>
                        </legend>

                        <x-forms.addresses-search
                            :address="$address"
                            :districts="$districts"
                            :settlements="$settlements"
                            :streets="$streets"
                            :readonly="$readonly"
                            class="mt-8 form-row-3"
                        />
                        <div class="form-group checkbox-group">
                            <input wire:model='formService.division.is_mountainous' id="is_mountainous" type="checkbox" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                            <label for="is_mountainous" class="checkbox-label text-gray-500 dark:text-gray-300 ms-2">{{__('forms.mountainous_status')}}</label>
                        </div>

                    </fieldset>

                    {{-- WORKING HOURS --}}
                    <fieldset class="fieldset" x-data="{ working: false }">
                        <legend class="legend">
                            <h2>{{ __('forms.work_schedule') }}</h2>
                        </legend>

                        <div class="form">
                            <div class="form-group mb-4">
                                <button
                                        @click.prevent="working = !working"
                                        x-text="working ? '{{ __('Прибрати графік роботи') }}' : '{{ __('forms.add_work_schedule') }}'"
                                        class="item-add"
                                >
                                    {{ __('add_work_schedule') }}
                                </button>
                            </div>

                            @if($action === 'store')
                                <div x-show="working" x-cloak class="p-4 rounded-lg bg-blue-100 flex items-start mb-4">
                                    <svg class="w-6 h-6 text-blue-500 mr-3 mt-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13V8m0 8h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                    </svg>
                                    <div>
                                        <p class="font-bold text-blue-800">{{ __('Важливо') }}</p>
                                        <p class="text-sm text-blue-600">{{ __("forms.schedule_note") }}</p>
                                    </div>
                                </div>
                            @endif

                            @if ($weekdays)
                                <div x-show="working" x-cloak class="grid md:grid-cols-2 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                                    @foreach ($weekdays as $key => $day)
                                        <div class="p-6 min-h-[220px] {{ $loop->iteration % 2 == 0 ? '' : 'border-r border-gray-200 dark:border-gray-700' }} {{ $loop->last ? '' : 'border-b border-gray-200 dark:border-gray-700' }} ">
                                            <div x-data="{
                            shift: @json(count($divisionForm->getDivisionParam('working_hours')[$key]) > 1),
                            show_work: @json(!empty($divisionForm->getDivisionParam('working_hours')[$key][0]) || $action === 'store'),
                            checkShift() {
                                this.shift = !this.show_work;
                            }
                        }">

                                                <div class="mb-4">
                                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $day }}</h3>
                                                </div>

                                                <div class="flex items-center gap-8 mb-4">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" class="sr-only peer"
                                                               x-model="show_work"
                                                               @change="$wire.notWorking('{{ $key }}', !show_work)"
                                                               x-on:click="checkShift()"
                                                               x-bind:disabled="isDisabled"
                                                        >
                                                        <div class="relative w-11 h-6 bg-gray-200 rounded-full peer peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:bg-gray-700 dark:peer-focus:ring-blue-800 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full"></div>
                                                        <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                                                              x-text="show_work ? '{{ __('Працює') }}' : '{{ __('Не працює') }}'"></span>
                                                    </label>

                                                    <label class="inline-flex items-center cursor-pointer" x-bind:class="!show_work && 'opacity-40 pointer-events-none'">
                                                        <input type="checkbox"
                                                               x-model="shift"
                                                               @change="$wire.noShift('{{ $key }}', !shift)"
                                                               x-bind:disabled="isDisabled"
                                                               id="is_mountainous"
                                                               class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                                        />
                                                        <span class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __('Позмінно') }}</span>
                                                    </label>
                                                </div>

                                                <template x-if="show_work">
                                                    <div class="mt-3 space-y-4">
                                                        @if ($action === 'store' || !empty($divisionForm->getDivisionParam('working_hours')[$key]))
                                                            @foreach ($divisionForm->getDivisionParam('working_hours')[$key] as $shiftIndex => $shift_hours)
                                                                <div class="space-y-4">
                                                                    <template x-if="shift">
                                                                        <div class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                                                            <span>{{ __(':n зміна', ['n' => $shiftIndex + 1]) }}</span>
                                                                        </div>
                                                                    </template>

                                                                    <div class="flex items-end gap-4">
                                                                        <div class="form-group w-full">
                                                                            <label for="opened_by-{{ $key }}-{{ $shiftIndex }}"
                                                                                   class="label !text-xs !text-gray-500 dark:!text-gray-400">
                                                                                <span x-text="shift ? '{{ __('Початок') }}' : '{{ __('forms.openedBy') }}'"></span>
                                                                            </label>
                                                                            <div class="relative w-full">
                                                                                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none">
                                                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                                                    </svg>
                                                                                </div>
                                                                                <input
                                                                                        type="time"
                                                                                        id="opened_by-{{ $key }}-{{ $shiftIndex }}"
                                                                                        class="input text-gray-900 dark:text-white border-t-0 border-r-0 border-l-0 border-b border-gray-300 focus:ring-0 px-0 ps-8"
                                                                                        wire:model="divisionForm.division.working_hours.{{ $key }}.{{ $shiftIndex }}.0"
                                                                                        x-bind:disabled="isDisabled"
                                                                                />
                                                                            </div>
                                                                            @error("divisionForm.division.working_hours.{{ $key }}.{{ $shiftIndex }}.0")
                                                                            <p class="text-error">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>

                                                                        <div class="form-group w-full">
                                                                            <label for="closed_by-{{ $key }}-{{ $shiftIndex }}"
                                                                                   class="label !text-xs !text-gray-500 dark:!text-gray-400">
                                                                                <span x-text="shift ? '{{ __('Кінець') }}' : '{{ __('forms.closedBy') }}'"></span>
                                                                            </label>
                                                                            <div class="relative w-full">
                                                                                <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none">
                                                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                                                    </svg>
                                                                                </div>
                                                                                <input
                                                                                        type="time"
                                                                                        id="closed_by-{{ $key }}-{{ $shiftIndex }}"
                                                                                        class="input text-gray-900 dark:text-white border-t-0 border-r-0 border-l-0 border-b border-gray-300 focus:ring-0 px-0 ps-8"
                                                                                        wire:model="divisionForm.division.working_hours.{{ $key }}.{{ $shiftIndex }}.1"
                                                                                        x-bind:disabled="isDisabled"
                                                                                />
                                                                            </div>
                                                                            @error("divisionForm.division.working_hours.{{ $key }}.{{ $shiftIndex }}.1")
                                                                            <p class="text-error">{{ $message }}</p>
                                                                            @enderror
                                                                        </div>

                                                                        <button type="button"
                                                                                x-show="shift && {{ $shiftIndex > 0 ? 'true' : 'false' }}"
                                                                                wire:click="deleteShift('{{ $key }}', '{{ $shiftIndex }}')"
                                                                                class="h-10 text-gray-800 dark:text-gray-500 hover:text-gray-600">
                                                                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @endif
                                                            @if (count($divisionForm->getDivisionParam('working_hours')[$key]) < 4)
                                                                <button
                                                                        x-show='shift'
                                                                        class='item-add text-sm'
                                                                        @click.prevent=''
                                                                        wire:click="addAvailableShift('{{ $key }}')"
                                                                        x-bind:disabled="isDisabled"
                                                                >
                                                                    {{ __('forms.add_shift') }}
                                                                </button>
                                                            @endif
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </fieldset>

                    <div class='mb-4.5 mt-6 flex flex-col gap-6 xl:flex-row justify-left items-center'>
                        <a role="button" class="alternative-button cursor-pointer" href="javascript:history.back()">
                            {{ __('forms.back') }}
                        </a>

                        @yield('additional-buttons')
                    </div>

                    <div
                        wire:loading
                        role='status'
                        class='absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2'
                    >
                        <svg
                            aria-hidden='true'
                            class='w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600'
                            viewBox='0 0 100 101'
                            fill='none'
                            xmlns='http://www.w3.org/2000/svg'
                        >
                            <path
                                d='M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z'
                                fill='currentColor'
                            />
                            <path
                                d='M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z'
                                fill='currentFill'
                            />
                        </svg>
                    </div>
                </form>
         </div>
    </section>
</div>



