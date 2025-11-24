<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Contract\Forms\ReimbursementContractRequestForm as Form;
use App\Models\ContractRequest;
use App\Models\LegalEntity;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReimbursementContractCreate extends ContractComponent
{
    public Form $form;

    public bool $showSignatureModal = false;

    protected array $dictionaryNames = [
        'CONTRACT_TYPE',
        'REIMBURSEMENT_CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT'
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);
    }

    public function openModalSigned(): void
    {
        $this->showSignatureModal = true;
    }

    public function create(): void
    {
        if (Auth::user()?->cannot('initialize', ContractRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на ініціалізацію запиту на створення контракту');

            return;
        }

        try {
            $response = EHealth::contractRequest()->initialize('reimbursement');
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when initializing contract request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ.");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when initializing contract request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $signedContent = signatureService()->signData(
            $validated,
            $validated['password'],
            $validated['knedp'],
            $validated['keyContainerUpload'],
            Auth::user()->party->taxId
        );

        try {
            EHealth::contractRequest()->create(
                $response->validate()['id'],
                'reimbursement',
                ['signed_content' => $signedContent, 'signed_content_encoding' => 'base64']
            );
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating a contract');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ.");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating a contract');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.contract.reimbursement-contract-create');
    }
}
