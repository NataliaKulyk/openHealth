<?php

namespace App\Livewire\License\Forms;

use App\Core\Arr;
use App\Models\License;
use Livewire\Attributes\Locked;
use Livewire\Form;

class LicenseForm extends Form
{
    #[Locked]
    public bool $isPrimary = false;
    public string $type = '';
    public string $orderNo = '';
    public string $issuedBy = '';
    public string $issuedDate = '';
    public string $whatLicensed = '';
    public string $licenseNumber = '';
    public string $activeFromDate = '';
    public string $expiryDate = '';

    /**
     * Populate the form with the data of the requested license.
     */
    public function setLicense(License $license): void
    {
        $this->fill(
            Arr::toCamelCase($license->toArray()),
        );
    }

    /**
     * Set validation rules for the form.
     */
    protected function rules(): array
    {

    }
}
