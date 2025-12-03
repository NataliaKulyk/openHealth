<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Livewire\Contract\Forms\CapitationContractRequestForm as Form;
use App\Models\LegalEntity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CapitationContractCreate extends ContractComponent
{
    public Form $form;

    public array $legalEntities;

    /**
     * List of related divisions
     *
     * @var array
     */
    public array $divisions;

    protected array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'MEDICAL_SERVICE',
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);

        $this->legalEntities = LegalEntity::get(['id', 'edr'])->toArray();

        $this->divisions = $legalEntity->divisions->where('status', Status::ACTIVE)->toArray();
    }

    public function createLocally(): void
    {
        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }
    }

    public function create(): void
    {
        try {
            $validated = $this->form->validate();
            $validatedCipher = $this->form->validate($this->form->signingRules()());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $signedContent = signatureService()->signData(
            $this->form->formatForApi($validated),
            $validatedCipher['password'],
            $validatedCipher['knedp'],
            $validatedCipher['keyContainerUpload'],
            Auth::user()->party->taxId
        );

        $response = EHealth::contractRequest()->create(
            '4b0b9001-7ecd-41a0-ac0d-b9030fce6fcb',
            'capitation',
            ['signed_content' => $signedContent, 'signed_content_encoding' => 'base64']
        );
    }

    public function render(): View
    {
        return view('livewire.contract.capitation-contract-create');
    }
}
