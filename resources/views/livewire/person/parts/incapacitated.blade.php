<fieldset class="fieldset"
          x-data="{ isIncapacitated: $wire.entangle('isIncapacitated') }"
>
    <legend class="legend flex items-baseline gap-2">
        <x-checkbox class="default-checkbox mb-2"
                    x-model="isIncapacitated"
                    id="isIncapacitated"
        />
        {{ __('patients.incapacitated') }}
    </legend>

    <div x-show="isIncapacitated" x-cloak x-transition>
        @include('livewire.person.parts.search-confidant-person')
        @include('livewire.person.parts.confidant-person')
    </div>
</fieldset>
