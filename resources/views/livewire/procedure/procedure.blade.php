@php
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
@endphp

<div aria-hidden="true" class="hidden">
    {!! $svgSprite !!}
</div>

<section class="section-form">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('patients.procedures') }} - {{ $patientFullName }}
        </x-slot>
    </x-section-navigation>

    <form class="form"
          x-data="{
              procedures: $wire.entangle('form.procedures'),
              modalProcedure: new Procedure()
          }"
    >

        @include('livewire.encounter.procedure-parts.main-information', ['context' => 'procedure'])
        @include('livewire.encounter.procedure-parts.additional-information', ['context' => 'procedure'])
        @include('livewire.encounter.procedure-parts.reason-references')
        @include('livewire.encounter.procedure-parts.used-codes')

        <div class="flex gap-8">
            <button wire:click.prevent="" type="submit" class="button-minor">
                {{ __('forms.delete') }}
            </button>

            <button @click.prevent="$wire.save(modalProcedure)" type="submit" class="button-primary">
                {{ __('forms.save') }}
            </button>

            <button wire:click.prevent="openModal('signedContent')"
                    type="button"
                    class="button-sync flex items-center gap-2"
            >
                <svg width="16" height="17">
                    <use xlink:href="#svg-key"></use>
                </svg>
                {{ __('forms.complete_the_interaction_and_sign') }}
                <svg width="16" height="17">
                    <use xlink:href="#svg-arrow-right"></use>
                </svg>
            </button>
        </div>

        @if($showModal === 'signedContent')
            @include('livewire.procedure.modals.sign-content')
        @endif
    </form>

    <x-messages/>
    <x-forms.loading/>
</section>

<script>
    /**
     * Representation of the user's personal procedure
     */
    class Procedure {
        isReferralAvailable = true;
        referralType = '';
        paperReferral = {
            requesterLegalEntityEdrpou: '',
            requesterLegalEntityName: '',
            serviceRequestDate: ''
        };
        category = {
            coding: [{ system: 'eHealth/procedure_categories', code: '' }],
            text: ''
        };
        code = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'service' }],
                    text: ''
                },
                value: ''
            }
        };
        recordedBy = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'employee' }],
                    text: ''
                }
            }
        };
        division = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'division' }],
                    text: ''
                },
                value: ''
            }
        };
        outcome = {
            coding: [{ system: 'eHealth/procedure_outcomes', code: '' }],
            text: ''
        };
        primarySource = true;
        performer = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'employee' }],
                    text: ''
                }
            }
        };
        reportOrigin = {
            coding: [{ system: 'eHealth/report_origins', code: '' }],
            text: ''
        };
        reasonReferences = [];
        usedCodes = [];
        complicationDetails = [];

        // Create date
        #now = new Date();
        #endTime = new Date(this.#now.getTime() + 15 * 60 * 1000); // add 15 minutes

        performedPeriodStartDate = this.#now.toISOString().split('T')[0];
        performedPeriodStartTime = this.#now.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        performedPeriodEndDate = this.#endTime.toISOString().split('T')[0];
        performedPeriodEndTime = this.#endTime.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        constructor(obj = null) {
            if (obj) {
                this.procedures = JSON.parse(JSON.stringify(obj.procedures || obj));
            }
        }
    }
</script>
