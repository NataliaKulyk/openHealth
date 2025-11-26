<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{ __('contracts.payment_details') }}</h2>
    </legend>

    <p class="default-p mb-6">{{ __('contracts.payment_details_info') }}</p>

    <div class="form-row-2">
        <div class="form-group">
            <input wire:model="form.contractorPaymentDetails.bankName"
                   type="text"
                   name="bankName"
                   id="bankName"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label for="bankName" class="label">{{ __('contracts.bank_name') }}</label>

            @error('form.contractorPaymentDetails.bankName')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <input wire:model="form.contractorPaymentDetails.MFO"
                   type="text"
                   name="MFO"
                   id="MFO"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label for="MFO" class="label">{{ __('contracts.mfo') }}</label>

            @error('form.contractorPaymentDetails.MFO')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group">
            <input wire:model="form.contractorPaymentDetails.payerAccount"
                   type="text"
                   x-mask="UA99 9999999 999999999999999999"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label class="label">{{ __('contracts.payer_account') }}</label>

            @error('form.contractorPaymentDetails.payerAccount')
            <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</fieldset>
