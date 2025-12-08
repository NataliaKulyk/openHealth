<div>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('Перегляд договору') }} {{ $contract->contract_number ?? 'Чернетка' }}
        </x-slot>
    </x-header-navigation>

    <div class="form shift-content space-y-6">

        {{-- Status and Reason Section --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2">
                {{ __('Статус документа') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="label">{{ __('Поточний статус') }}</label>
                    <div class="mt-1">
                        <span class="{{ $contract->status->color() }} text-sm font-bold px-2 py-1 rounded">
                            {{ $contract->status->label() }}
                        </span>
                    </div>
                </div>

                {{-- Requirement 3.1.5.1: Show status reason if present --}}
                @if($contract->status_reason)
                    <div class="col-span-1 md:col-span-2 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                @icon('exclamation-circle', 'h-5 w-5 text-red-400')
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 dark:text-red-200">
                                    <span class="font-bold">{{ __('Причина зміни статусу:') }}</span>
                                    {{ $contract->status_reason }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Main Info --}}
        <fieldset disabled class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2">
                {{ __('Основна інформація') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="label">{{ __('Номер договору') }}</label>
                    {{-- Use null coalescing operator to show placeholder if number is missing --}}
                    <input type="text" class="input" value="{{ $contract->contract_number ?? '---' }}">
                </div>

                <div>
                    <label class="label">{{ __('Тип договору') }}</label>
                    <input type="text" class="input" value="{{ $contract->type ?? '---' }}">
                </div>

                {{-- Parse string date to Carbon before formatting --}}
                <div>
                    <label class="label">{{ __('Дата початку') }}</label>
                    <input type="text" class="input"
                           value="{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d.m.Y') : '---' }}">
                </div>

                {{-- Parse string date to Carbon before formatting --}}
                <div>
                    <label class="label">{{ __('Дата завершення') }}</label>
                    <input type="text" class="input"
                           value="{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d.m.Y') : '---' }}">
                </div>

                <div>
                    <label class="label">{{ __('Форма (ID Form)') }}</label>
                    <input type="text" class="input" value="{{ $contract->id_form ?? '---' }}">
                </div>
            </div>
        </fieldset>

        {{-- Medical Programs --}}
        @if(!empty($contract->medical_programs))
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2">
                    {{ __('Медичні програми') }}
                </h3>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($contract->medical_programs as $programId)
                        <li class="text-gray-700 dark:text-gray-300 text-sm font-mono">
                            {{ $programId }}
                            {{-- TODO: Add logic to fetch Program Name by ID from Dictionary/Cache --}}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Payment Details --}}
        <fieldset disabled class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow space-y-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b pb-2">
                {{ __('Реквізити оплати') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="label">{{ __('Банк') }}</label>
                    <input type="text" class="input"
                           value="{{ $contract->contractor_payment_details['bank_name'] ?? '' }}">
                </div>
                <div>
                    <label class="label">{{ __('Рахунок (IBAN)') }}</label>
                    <input type="text" class="input"
                           value="{{ $contract->contractor_payment_details['payer_account'] ?? '' }}">
                </div>
            </div>
        </fieldset>

        {{-- Action Buttons --}}
        <div class="flex justify-between items-center pt-6">
            <a href="{{ route('contract.index', legalEntity()) }}" class="button-minor">
                &larr; {{ __('forms.back_to_list') }}
            </a>

            {{-- Example: Add 'Edit' button logic here if editing is allowed for specific statuses --}}
        </div>
    </div>
</div>
