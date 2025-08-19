@php
    $hasZipError = $errors->has('address.zip');
    $hasApartmentError = $errors->has('address.apartment');
    $hasBuildingError = $errors->has('address.building');
    $hasAreaError = $errors->has('address.area');
    $hasSettlementTypeError = $errors->has('address.settlementType');
    $hasRegionError = $errors->has('address.region');
    $hasSettlementError = $errors->has('address.settlement');
    $hasStreetTypeError = $errors->has('address.streetType');
    $hasStreetError = $errors->has('address.street');

    natcasesort($dictionaries['STREET_TYPE']);
@endphp

<div class="{{ $class }}">
    {{-- AREA --}}
    <div class="form-group group !z-[18]">
        <select wire:model.live="address.area"
                required
                id="addressArea"
                aria-describedby="{{ $hasAreaError ? 'addressAreaErrorHelp' : '' }}"
                class="input-select text-gray-800 {{ $hasAreaError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                :disabled="{{ $readonly ? 'true' : 'false' }}"
        >
            <option value="_placeholder_" hidden>-- {{ __('forms.select') }} --</option>
            @nonempty($regions)
                @foreach ($regions as $regionItem)
                    <option value="{{ $regionItem['name'] }}">
                        {{ $regionItem['name'] }}
                    </option>
                @endforeach
            @endnonempty
        </select>

        @if($hasAreaError)
            <p id="addressAreaErrorHelp" class="text-error">
                {{ $errors->first('address.area') }}
            </p>
        @endif

        <label for="addressArea" class="label z-10">
            {{ __('forms.area') }}
        </label>
    </div>

    {{-- REGION --}}
    <div class="form-group group !z-[17]">
        <div @mouseleave="timeout = setTimeout(() => { showTo = false }, 800)"
             x-data="{
                showTo: false,
                srch: $wire.entangle('districtsSearching'),
                init() {
                    $watch('srch', (value) => {
                        if (value) {
                            this.showTo = true;
                        }
                    })
                }
            }"
        >
            <input wire:model.live.debounce.400ms="address.region"
                   type="text"
                   placeholder=" "
                   id="addressRegion"
                   autocomplete="off"
                   x-ref="regionField"
                   aria-describedby="{{ $hasRegionError ? 'addressRegionErrorHelp' : '' }}"
                   class="input {{ $hasRegionError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                   :disabled="{{ empty($address['area']) || (isset($address['area']) && $address['area']) === 'М.КИЇВ' || $readonly ? 'true' : 'false' }}"
            />

            <template x-if="showTo">
                <div @click.away="showTo = false"
                     x-transition
                     class="absolute left-0 right-0 top-full bg-white border border-gray-300 rounded-bl-md rounded-br-md shadow-lg dark:bg-gray-800 dark:border-gray-500"
                >
                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownHoverButton">
                        @forelse ($districts as $district)
                            <li @click="
                                    $refs.regionField.value = '{{ str_replace("'", "\\'", $district['name']) }}';
                                    $wire.call('selectDistrict', '{{ str_replace("'", "\\'", $district['name']) }}');
                                    showTo = false;
                                "
                                class="cursor-pointer px-4 py-2 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-blue-800"
                            >
                                {{ $district['name'] }}
                            </li>
                        @empty
                            <li class="cursor-default px-4 py-2">
                                {{ __('forms.nothing_found') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </template>

            @if($hasRegionError)
                <p id="addressRegionErrorHelp" class="text-error">
                    {{ $errors->first('address.region') }}
                </p>
            @endif

            <label for="addressRegion" class="label z-10">
                {{ __('forms.region') }}
            </label>
        </div>
    </div>

    {{-- TYPE --}}
    <div class="form-group group !z-[16]">
        <select wire:model.live="address.settlementType"
                required
                id="addressSettlementType"
                aria-describedby="{{ $hasSettlementTypeError ? 'addressSettlementTypeErrorHelp' : '' }}"
                class="input-select text-gray-800 {{ $hasSettlementTypeError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                :disabled="{{ empty($address['region']) || $readonly ? 'true' : 'false' }}"
        >
            <option value="_placeholder_" selected hidden>-- {{ __('forms.select') }} --</option>

            @isset($dictionaries['SETTLEMENT_TYPE'])
                @foreach($dictionaries['SETTLEMENT_TYPE'] as $key => $type)
                    <option class="normal-case"
                            {{ isset($address['settlementType']) && $address['settlementType'] === $key ? 'selected': ''}}
                            value="{{ $key }}"
                    >
                        {{ $type }}
                    </option>
                @endforeach
            @endif
        </select>

        @if($hasSettlementTypeError)
            <p id="addressSettlementTypeErrorHelp" class="text-error">
                {{ $errors->first('address.settlementType') }}
            </p>
        @endif

        <label for="addressSettlementType" class="label z-10">
            {{ __('forms.settlement_type') }}
        </label>
    </div>

    {{-- SETTLEMENT --}}
    <div class="form-group group !z-[15]">
        <div @mouseleave="timeout = setTimeout(() => { showTo = false }, 800)"
             x-data="{
                showTo: false,
                srch: $wire.entangle('settlementsSearching'),
                init() {
                    $watch('srch', (value) => {
                        if (value) {
                            this.showTo = true;
                        }
                    })
                },
            }"
        >
            <input wire:model.live.debounce.400ms="address.settlement"
                   required
                   type="text"
                   placeholder=" "
                   id="addressSettlement"
                   autocomplete="off"
                   x-ref="settlementField"
                   aria-describedby="{{ $hasSettlementError? 'addressSettlementErrorHelp' : '' }}"
                   class="input {{ $hasSettlementError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                   :disabled="{{ empty($address['settlementType']) || (isset($address['area']) && $address['area']) === 'М.КИЇВ' || $readonly ? 'true' : 'false' }}"
            />

            <template x-if="showTo">
                <div @click.away="showTo = false"
                     x-transition
                     class="absolute left-0 right-0 top-full bg-white border border-gray-300 rounded-bl-md rounded-br-md shadow-lg dark:bg-gray-800 dark:border-gray-500"
                >
                    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownHoverButton">
                        @forelse ($settlements as $settlement)
                            <li @click="
                                    $refs.settlementField.value = '{{ str_replace("'", "\\'", $settlement['name']) }}';
                                    $wire.call('selectSettlements', '{{ str_replace("'", "\\'", $settlement['name']) }}', '{{ $settlement['id'] }}');
                                    showTo = false;
                                "
                                class="cursor-pointer px-4 py-2 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-blue-800"
                            >
                                {{ $settlement['name'] }}
                            </li>
                        @empty
                            <li class="cursor-default px-4 py-2">
                                {{ __('forms.nothing_found') }}
                            </li>
                        @endforelse
                    </ul>
                </div>
            </template>

            @if($hasSettlementError)
                <p id="addressSettlementErrorHelp" class="text-error">
                    {{ $errors->first('address.settlement') }}
                </p>
            @endif

            <label for="addressSettlement" class="label z-10">
                {{ __('forms.settlement') }}
            </label>
        </div>
    </div>

    {{-- STREET_TYPE --}}
    <div class="form-group group !z-[14]">
        <select wire:model.live="address.streetType"
                id="addressStreetType"
                aria-describedby="{{ $hasStreetTypeError ? 'addressStreetTypeErrorHelp' : '' }}"
                class="input-select text-gray-800 {{ $hasStreetTypeError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                :disabled="{{ empty($address['settlement']) || $readonly ? 'true' : 'false' }}"
        >
            <option value="_placeholder_" selected hidden>-- {{ __('forms.select') }} --</option>

            @if($dictionaries['STREET_TYPE'])
                @foreach($dictionaries['STREET_TYPE'] as $key => $type)
                    <option class="normal-case"
                            {{ isset($address['streetType']) && $address['streetType'] === $key ? 'selected': ''}}
                            value="{{ $key }}"
                    >
                        {{ $type }}
                    </option>
                @endforeach
            @endif
        </select>

        @if($hasStreetTypeError)
            <p id="addressStreetTypeErrorHelp" class="text-error">
                {{ $errors->first('address.streetType') }}
            </p>
        @endif

        <label for="addressStreetType" class="label absolute z-20">
            {{ __('forms.street_type') }}
        </label>
    </div>

    {{-- STREET --}}
    <div class="form-group group !z-[13]">
        <input wire:model.live.debounce.400ms="address.street"
               type="text"
               placeholder=" "
               id="addressStreet"
               autocomplete="off"
               x-ref="streetField"
               aria-describedby="{{ $hasStreetError ? 'addressStreetErrorHelp' : '' }}"
               class="input {{ $hasStreetError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
               :disabled="{{ empty($address['settlementType']) || $readonly ? 'true' : 'false' }}"
        />

        <div @mouseleave="timeout = setTimeout(() => { showTo = false }, 800)"
             x-data="{
                showTo: false,
                srch: $wire.entangle('streetsSearching'),
                init() {
                    $watch('srch', (value) => {
                            this.showTo = true;
                    })
                }
            }"
        >
            <div @click.away="showTo = false"
                 x-cloak
                 x-transition
                 x-show="showTo"
                 class="absolute left-0 right-0 top-full bg-white border border-gray-300 rounded-bl-md rounded-br-md shadow-lg dark:bg-gray-800 dark:border-gray-500"
                 :disabled="{{ $readonly ? 'true' : 'false' }}"
            >
                <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownHoverButton">
                    @forelse ($streets as $street)
                        <li @click="
                                $refs.streetField.value = '{{ str_replace("'", "\\'", $street['name']) }}';
                                $wire.call('selectStreets', '{{ str_replace("'", "\\'", $street['name']) }}');
                                showTo = false;
                            "
                            class="cursor-pointer px-4 py-2 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-blue-800"
                        >
                            {{ $street['name'] }}
                        </li>
                    @empty
                        <li class="cursor-default px-4 py-2">
                            {{ __('forms.nothing_found') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        @if($hasStreetError)
            <p id="addressStreetErrorHelp" class="text-error">
                {{ $errors->first('address.street') }}
            </p>
        @endif

        <label for="addressStreet" class="label z-10">
            {{ __('forms.street') }}
        </label>
    </div>

    {{-- BUILDING --}}
    <div class="form-group group !z-[12]">
        <input wire:model="address.building"
               type="text"
               placeholder=" "
               id="addressBuilding"
               aria-describedby="{{ $hasBuildingError ? 'addressBuildingErrorHelp' : '' }}"
               class="input {{ $hasBuildingError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
               :disabled="{{ empty($address['settlement']) || $readonly ? 'true' : 'false' }}"
        />

        @if($hasBuildingError)
            <p id="addressBuildingErrorHelp" class="text-error">
                {{ $errors->first('address.building') }}
            </p>
        @endif

        <label for="addressBuilding" class="label z-10">
            {{ __('forms.building') }}
        </label>
    </div>

    {{-- APARTMENT --}}
    <div class="form-group group !z-[11]">
        <input wire:model="address.apartment"
               type="text"
               placeholder=" "
               id="addressApartment"
               aria-describedby="{{ $hasApartmentError ? 'addressApartmentErrorHelp' : '' }}"
               class="input {{ $hasApartmentError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
               :disabled="{{ empty($address['settlement']) || $readonly ? 'true' : 'false' }}"
        />

        @if($hasApartmentError)
            <p id="addressApartmentErrorHelp" class="text-error">
                {{ $errors->first('address.apartment') }}
            </p>
        @endif

        <label for="addressApartment" class="label z-10">
            {{ __('forms.apartment') }}
        </label>
    </div>

    {{-- ZIP --}}
    <div class="form-group group">
        <input wire:model="address.zip"
               type="text"
               x-mask="99999"
               placeholder=" "
               id="addressZip"
               aria-describedby="{{ $hasZipError ? 'addressZipErrorHelp' : '' }}"
               class="input {{ $hasZipError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
               :disabled="{{ empty($address['settlement']) || $readonly ? 'true' : 'false' }}"
        />

        @if($hasZipError)
            <p id="addressZipErrorHelp" class="text-error">
                {{ $errors->first('address.zip') }}
            </p>
        @endif

        <label for="addressZip" class="label z-10">
            {{ __('forms.zip_code') }}
        </label>
    </div>
</div>
