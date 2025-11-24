<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

class CapitationContractRequestForm extends BaseContractRequestForm
{
    public function rules(): array
    {
        return array_merge(parent::rules(), []);
    }
}
