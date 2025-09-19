@use('App\Enums\License\Type')
@use('Carbon\CarbonImmutable')

<section class="section-form">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <x-section-navigation class="breadcrumb-form flex-1 min-w-0">
            <x-slot name="title">
                {{ __('licenses.details') }}
            </x-slot>
        </x-section-navigation>
    </div>

    <fieldset class="fieldset">
        <legend class="legend">
            {{ __('licenses.details') }}
        </legend>

        <div class="form-row-2">
            <div class="form-group group">
                <label for="isPrimary" class="label">
                    {{ __('licenses.kind') }}
                </label>
                <input value="{{ $license->isPrimary ? __('licenses.primary') : __('licenses.not_primary') }}"
                       type="text"
                       name="isPrimary"
                       id="isPrimary"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <label for="type" class="label">
                    {{ __('licenses.type') }}
                </label>
                <input value="{{ $license->type->label() }}"
                       type="text"
                       name="type"
                       id="type"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>

            <div class="form-group group">
                <label for="orderNo" class="label">
                    {{ __('licenses.order_no') }}
                </label>
                <input value="{{ $license->order_no }}"
                       type="text"
                       name="orderNo"
                       id="orderNo"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <label for="issuedBy" class="label">
                    {{ __('licenses.issued_by') }}
                </label>
                <input value="{{ $license->issued_by }}"
                       type="text"
                       name="issuedBy"
                       id="issuedBy"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>

            <div class="form-group group">
                <label for="whatLicensed" class="label">
                    {{ __('licenses.what_licensed') }}
                </label>
                <input value="{{ $license->what_licensed }}"
                       type="text"
                       name="whatLicensed"
                       id="whatLicensed"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <label for="number" class="label">
                    {{ __('licenses.number') }}
                </label>
                <input value="{{ $license->license_number }}"
                       type="text"
                       name="number"
                       id="number"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>

            <div class="form-group group">
                <label for="issuedDate" class="label">
                    {{ __('licenses.issued_date') }}
                </label>
                <input value="{{ CarbonImmutable::parse($license->issued_date)->format('d.m.Y') }}"
                       type="text"
                       name="issuedDate"
                       id="issuedDate"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <label for="activeFromDate" class="label">
                    {{ __('licenses.active_from_date') }}
                </label>
                <input value="{{ CarbonImmutable::parse($license->active_from_date)->format('d.m.Y') }}"
                       type="text"
                       name="activeFromDate"
                       id="activeFromDate"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>

            <div class="form-group group">
                <label for="expiryDate" class="label">
                    {{ __('licenses.expiry_date') }}
                </label>
                <input value="{{ CarbonImmutable::parse($license->expiry_date)->format('d.m.Y') }}"
                       type="text"
                       name="expiryDate"
                       id="expiryDate"
                       class="input peer"
                       placeholder=" "
                       disabled
                       autocomplete="off"
                />
            </div>
        </div>

        <a href="{{ url()->previous() }}" type="submit" class="button-minor">
            {{ __('forms.back') }}
        </a>
    </fieldset>
</section>
