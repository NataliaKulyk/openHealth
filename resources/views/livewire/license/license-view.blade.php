<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('Деталі ліцензії') }}</x-slot>
    </x-section-navigation>
    <form class="form">
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="licenseKind" id="licenseKind" class="peer input dark:text-gray-400" value="Основна" placeholder=" " required />
                <label for="licenseKind" class="label">Вид ліцензії</label>
            </div>
            <div class="form-group">
                <input type="text" name="OrderNumber" id="OrderNumber" class="peer input dark:text-gray-400" value="123123" placeholder=" " required />
                <label for="OrderNumber" class="label">Номер наказу</label>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <input type="text" name="licenseSeriesNumber" id="licenseSeriesNumber" class="peer input dark:text-gray-400" value="Провадження господарської діяльності з медичної практики" placeholder=" " required />
                <label for="LicenseSeriesNumber" class="label">Тип ліцензії</label>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="IssuedTheLicense" id="IssuedTheLicense" class="peer input dark:text-gray-400" value="МОЗ" placeholder=" " required />
                <label for="IssuedTheLicense" class="label">Ким видано</label>
            </div>
            <div class="form-group">
                <input type="text" name="licensedActivity" id="licensedActivity" class="peer input dark:text-gray-400" value="Лікарська діяльність" placeholder=" " required />
                <label for="licensedActivity" class="label">Напрям діяльності, що ліцензовано</label>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input type="text" name="licenseSeriesNumber" id="licenseSeriesNumber" class="peer input dark:text-gray-400" value="1231" placeholder=" " required />
                <label for="LicenseSeriesNumber" class="label">Серія та/або номер ліцензії</label>
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseIssuance" id="dateOfLicenseIssuance" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2025-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseIssuance" class="wrapped-label">Дата видачі ліцензії</label>
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseStartDate" id="dateOfLicenseStartDate" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2025-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseStartDate" class="wrapped-label">Дата початку дії ліцензії</label>
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="dateOfLicenseExpiry" id="dateOfLicenseExpiry" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2026-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="dateOfLicenseExpiry" class="wrapped-label">Дата завершення дії ліцензії</label>
            </div>
        </div>
        <div class="flex justify-start gap-4 mt-10">
            <button type="button" class="button-minor">
                Скасувати
            </button>
        </div>
    </form>
</div>
