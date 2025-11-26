<fieldset class="fieldset">
    @if(!empty($uploadedDocuments))
        <legend class="legend">
            {{ __('forms.uploading_documents') }}
        </legend>

        @foreach($uploadedDocuments as $key => $document)
            <div class="pb-4 flex" wire:key="{{ $key }}">
                <div class="flex-grow">
                    <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white"
                           for="file_input_{{ $key }}"
                    >
                        {{ $this->getDocumentLabel($document) }}
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="file"
                               class="xl:w-1/2 block text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                               id="file_input_{{ $key }}"
                               wire:model.live="form.uploadedDocuments.{{ $key }}"
                        >

                        @if(isset($patientRequest->uploadedDocuments[$key]) && !$errors->has("form.uploadedDocuments.$key"))
                            @if(!isset($uploadedFiles[$key]) || $uploadedFiles[$key] === false)
                                <button class="flex items-center gap-1"
                                        wire:click.prevent="deleteDocument({{ $key }})"
                                >
                                    <svg width="14" height="14" class="text-red-600">
                                        <use xlink:href="#svg-trash"></use>
                                    </svg>
                                    <span class="font-medium text-red-600 text-sm">{{ __('forms.delete') }}</span>
                                </button>
                            @else
                                <button class="flex items-center gap-1">
                                    <svg width="14" height="14">
                                        <use xlink:href="#svg-check-circle"></use>
                                    </svg>
                                    <span class="font-medium text-green-400 text-sm">{{ __('Відправлено') }}</span>
                                </button>
                            @endif
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                        {{ __('forms.max_file_size_and_format') }}
                    </p>

                    @error("form.uploadedDocuments.$key")
                    <p class="text-error">
                        {{ $message }}
                    </p>
                    @enderror
                </div>
            </div>
        @endforeach

        <div class="form-row-3">
            <div class="form-group group">
                <button wire:click.prevent="sendFiles"
                        class="button-primary mt-8 gap-2"
                        type="button"
                >
                    {{ __('forms.send_files') }}
                    @icon('arrow-right', 'w-4 h-4')
                </button>
            </div>
        </div>
    @endif

    @empty($uploadedDocuments)
        <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white">
            {{ __('forms.confirmation_code_from_SMS') }}
        </h2>

        <div class="flex flex-col md:flex-row gap-4 md:gap-6 {{ empty($uploadedDocuments) ? 'mt-0' : 'mt-8' }} mb-14">
            <div class="relative z-0 md:min-w-[33%] md:max-w-[33%]">
                <input wire:model="form.verificationCode"
                       type="text"
                       name="verificationCode"
                       id="verificationCode"
                       class="input peer @error('form.verificationCode') input-error @enderror"
                       placeholder=" "
                       required
                       maxlength="4"
                       autocomplete="off"
                />
                <label for="verificationCode" class="label">
                    {{ __('forms.confirmation_code_from_SMS') }}
                </label>

                @error('form.verificationCode')
                <p class="text-error">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <div>
                <button wire:click="approve"
                        type="button"
                        class="button-primary w-full"
                >
                    {{ __('forms.confirm') }}
                </button>
            </div>

            <!-- Resend SMS button -->
            <div>
                <button type="button"
                        wire:click.prevent="resendSms"
                        x-data="{
                            cooldown: $wire.entangle('resendCooldown'),
                            interval: null,
                            startCooldown() {
                                if (this.interval) {
                                    clearInterval(this.interval);
                                    this.interval = null;
                                }
                                if (this.cooldown > 0) {
                                    this.interval = setInterval(() => {
                                        if (this.cooldown > 0) {
                                            this.cooldown--;
                                        } else {
                                            clearInterval(this.interval);
                                            this.interval = null;
                                        }
                                    }, 1000);
                                }
                            },
                        }"
                        x-init="startCooldown()"
                        x-effect="startCooldown()"
                        :disabled="cooldown > 0"
                        :class="{ 'cursor-not-allowed': cooldown > 0 }"
                        class="button-minor gap-2 w-full"
                >
                    @icon('mail', 'w-4 h-4 text-gray-800 dark:text-white')
                    <span x-text="cooldown > 0 ? `Відправити ще раз (через ${cooldown} с)` : 'Відправити ще раз'">
                    </span>
                </button>
            </div>
        </div>
    @endempty
</fieldset>
