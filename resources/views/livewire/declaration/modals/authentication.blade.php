<div x-data="{ showAuthModal: $wire.entangle('showAuthModal') }">
    <template x-teleport="body">
        <div x-show="showAuthModal"
             style="display: none"
             @keydown.escape.prevent.stop="showAuthModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showAuthModal = false"
                 class="modal-wrapper"
            >
                <div @click.stop x-trap.noscroll.inert="showAuthModal"
                     class="modal-content w-full max-w-2xl mx-auto"
                >
                    <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white text-center">
                        {{ __('forms.authentication') }}
                    </h2>

                    <form>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="verificationCode" class="label-modal">
                                    {{ __('declarations.confirmation_code_from_SMS') }}
                                </label>
                                <input wire:model="form.verificationCode"
                                       id="verificationCode"
                                       name="verificationCode"
                                       type="text"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                       required
                                >

                                @error('form.verificationCode')
                                <p class="text-error">
                                    {{ $message }}
                                </p>
                                @enderror
                            </div>

                            <div class="form-group flex items-end">
                                <button wire:click.prevent="approve" type="button" class="button-primary">
                                    {{ __('forms.confirm') }}
                                </button>
                            </div>
                        </div>

                        {{-- Resend SMS --}}
                        <div class="form-row">
                            <div class="form-group">
                                <button type="button"
                                        x-data="{
                                            cooldown: 60,
                                            sentOnce: $wire.entangle('smsResent'),
                                            interval: null,
                                            modalOpened: false,
                                            startCooldown() {
                                                if (this.interval) {
                                                    clearInterval(this.interval);
                                                    this.interval = null;
                                                }

                                                this.cooldown = 60;

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
                                            resetCooldown() {
                                                this.cooldown = 60;
                                                if (this.interval) {
                                                    clearInterval(this.interval);
                                                    this.interval = null;
                                                }
                                            }
                                        }"
                                        x-init=""
                                        x-effect="if (showAuthModal && !modalOpened) { modalOpened = true; startCooldown(); }"
                                        wire:click.prevent="resendSms"
                                        @click="if (!sentOnce) {
                                            resetCooldown();
                                            startCooldown();
                                        }"
                                        :disabled="cooldown > 0 || sentOnce"
                                        :class="{ 'cursor-not-allowed': cooldown > 0 || sentOnce }"
                                        class="button-minor gap-2"
                                >
                                    <svg class="w-4 h-4 text-gray-800 dark:text-white" viewBox="0 0 16 17" fill="none"
                                         xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path fill="currentColor"
                                              d="M1.60254 5.20715L8.00014 8.40555L14.3977 5.20715C14.3741 4.79951 14.1954 4.41634 13.8984 4.13613C13.6014 3.85592 13.2085 3.69988 12.8001 3.69995H3.20014C2.79181 3.69988 2.3989 3.85592 2.10188 4.13613C1.80487 4.41634 1.62622 4.79951 1.60254 5.20715V5.20715Z"
                                        />
                                        <path fill="currentColor"
                                              d="M14.4001 6.99438L8.0001 10.1944L1.6001 6.99438V11.7C1.6001 12.1243 1.76867 12.5313 2.06873 12.8314C2.36878 13.1314 2.77575 13.3 3.2001 13.3H12.8001C13.2244 13.3 13.6314 13.1314 13.9315 12.8314C14.2315 12.5313 14.4001 12.1243 14.4001 11.7V6.99438Z"
                                        />
                                    </svg>
                                    <span
                                        x-text="sentOnce ? 'СМС вже відправлено' : (cooldown > 0 ? `Відправити ще раз (через ${cooldown} с)` : 'Відправити ще раз')">
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
