@php
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
@endphp

<div aria-hidden="true" class="hidden">
    {!! $svgSprite !!}
</div>

<section class="section-form">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('patients.diagnostic_reports') }} - {{ $patientFullName }}
        </x-slot>
    </x-section-navigation>

    <form class="form"
          x-data="{
              diagnosticReports: $wire.entangle('form.diagnosticReports'),
              modalDiagnosticReport: new DiagnosticReport(),
              diagnosticReportCategoriesDictionary: $wire.dictionaries['eHealth/diagnostic_report_categories'],
              servicesDictionary: $wire.dictionaries['custom/services']
          }"
    >

        @include('livewire.encounter.diagnostic-report-parts.main-information')
        @include('livewire.encounter.diagnostic-report-parts.additional-information', ['context' => 'diagnostic-report'])
        @include('livewire.encounter.parts.observations')

        <div class="flex gap-8">
            <button wire:click.prevent="" type="submit" class="button-minor">
                {{ __('forms.delete') }}
            </button>

            <button @click.prevent="$wire.save(modalDiagnosticReport)" type="submit" class="button-primary">
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
            @include('livewire.diagnostic-report.modals.sign-content')
        @endif
    </form>

    <x-messages/>
    <x-forms.loading/>
</section>

<script>
    /**
     * Representation of the user's personal diagnostic report.
     */
    class DiagnosticReport {
        category = [
            {
                coding: [{ system: 'eHealth/diagnostic_report_categories', code: '' }],
                text: ''
            }
        ];
        code = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'service' }],
                    text: ''
                },
                value: ''
            }
        };
        isReferralAvailable = false;
        referralType = '';
        basedOn = {
            identifier: {
                type: {
                    coding: [{ system: 'eHealth/resources', code: 'service_request' }],
                    text: ''
                }
            }
        };
        paperReferral = {
            requesterLegalEntityEdrpou: '',
            requesterLegalEntityName: '',
            serviceRequestDate: ''
        };
        conclusionCode = {
            coding: [{ system: 'eHealth/ICD10_AM/condition_codes', code: '' }]
        };
        primarySource = true;
        performer = {
            reference: {
                identifier: {
                    type: {
                        coding: [{ system: 'eHealth/resources', code: 'employee' }],
                        text: ''
                    }
                }
            }
        };
        reportOrigin = {
            coding: [{ system: 'eHealth/immunization_report_origins', code: '' }],
            text: ''
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
        resultsInterpreter = { text: '' };

        // Create date
        #now = new Date();
        #endTime = new Date(this.#now.getTime() + 15 * 60 * 1000); // add 15 minutes

        issuedDate = this.#now.toISOString().split('T')[0];
        issuedTime = this.#now.toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit', hour12: false });
        effectivePeriodStartDate = this.#now.toISOString().split('T')[0];
        effectivePeriodStartTime = this.#now.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
        effectivePeriodEndDate = this.#endTime.toISOString().split('T')[0];
        effectivePeriodEndTime = this.#endTime.toLocaleTimeString('uk-UA', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });

        constructor(obj = null) {
            if (obj) {
                this.diagnosticReports = JSON.parse(JSON.stringify(obj.diagnosticReports || obj));
            }
        }
    }
</script>
