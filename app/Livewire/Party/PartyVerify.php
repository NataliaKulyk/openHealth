<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Livewire\Component;
use Log;

class PartyVerify extends Component
{
    public Party $party;
    public LegalEntity $legalEntity;
    public array $verificationDetails = [];

    public bool $showUpdateModal = false;
    public string $status = '';
    public string $reason = '';
    public string $comment = '';

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->legalEntity = $legalEntity;
        $this->party = $party;
        $this->loadVerificationDetails();
    }

    public function loadVerificationDetails(): void
    {
        try {
            $response = EHealth::party()->getDetails($this->party->uuid);
            $this->verificationDetails = $response->validate();

        } catch (\Exception $e) {
            session()->flash('error', 'Не вдалося завантажити деталі верифікації.');
        }
    }

    /**
     * Handles the form submission to update the verification status.
     */
    public function updateStatus(): void
    {
        $this->validate(
            [
                'status'  => 'required|string',
                'reason'  => 'required|string',
                'comment' => 'nullable|string',
            ]
        );

        $payload = [
            'dracs_death' => [
                'verification_status' => $this->status,
                'verification_reason' => $this->reason,
                'verification_comment' => $this->comment,
            ]
        ];

        try {
            EHealth::party()->update($this->party->uuid, $payload);
            session()->flash('success', 'Статус успішно оновлено.');
            $this->loadVerificationDetails();

            Log::info('Dispatching status-updated-close-modal event after success.');
            $this->dispatch('status-updated-close-modal');

        } catch (EHealthValidationException $e) {
            Log::error('[PARTY UPDATE DEBUG] eHealth API returned a validation error.', [
                'party_uuid' => $this->party->uuid, 'payload_sent' => $payload, 'validation_errors' => $e->getErrors(),
            ]);
            session()->flash('error', 'Помилка валідації від ЕСОЗ: ' . $e->getTranslatedMessage());

        } catch (EHealthResponseException $e) {
            Log::error('[PARTY UPDATE DEBUG] eHealth API returned an error response.', [
                'party_uuid' => $this->party->uuid, 'status_code' => $e->getCode(), 'error_message' => $e->getMessage(),
            ]);
            $this->dispatch('flashMessage', ['message' => 'Помилка від ЕСОЗ: ' . $e->getMessage(), 'type' => 'error', 'persistent' => true]);
            Log::info('Dispatching status-updated-close-modal event after EHealthResponseException.');
            $this->dispatch('status-updated-close-modal');
        } catch (\Exception $e) {
            Log::error('[PARTY UPDATE DEBUG] Request failed with a generic error.', [
                'party_uuid' => $this->party->uuid, 'error_message' => $e->getMessage(),
            ]);
            session()->flash('error', 'Виникла неочікувана помилка: ' . $e->getMessage());
            Log::info('Dispatching status-updated-close-modal event after generic Exception.');
            $this->dispatch('status-updated-close-modal');
        }
    }

    public function render()
    {
        return view('livewire.party.party-verify');
    }
}
