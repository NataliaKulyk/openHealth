<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PartyVerify extends Component
{
    public Party $party;
    public LegalEntity $legalEntity;
    public array $verificationDetails = [];
    public string $stream = 'dracs_death';

    // Modal state
    public bool $showUpdateModal = false;

    // Form fields
    public string $status = '';
    public string $reason = '';
    public string $comment = '';
    public string $backUrl = '';

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->legalEntity = $legalEntity;
        $this->party = $party;
        $this->loadVerificationDetails();

        $nameChangeStatus = data_get($this->verificationDetails, 'details.dracs_name_change.verification_status');

        if ($nameChangeStatus === 'NOT_VERIFIED') {
            $this->stream = 'dracs_name_change';
        } else {
            $this->stream = 'dracs_death';
        }

        $previous = url()->previous();
        $current = request()->url();

        if ($previous !== $current && str_contains($previous, config('app.url'))) {
            $this->backUrl = $previous;
        } else {
            $this->backUrl = route('party.verification.index', ['legalEntity' => $legalEntity->id]);
        }
    }

    /**
     * Determines if there is any problem that can be solved manually.
     * If so, the button will be active.
     */
    #[Computed]
    public function canUpdateVerification(): bool
    {
        $deathStatus = data_get($this->verificationDetails, 'details.dracs_death.verification_status');
        $nameChangeStatus = data_get($this->verificationDetails, 'details.dracs_name_change.verification_status');

        return $deathStatus === 'NOT_VERIFIED' || $nameChangeStatus === 'NOT_VERIFIED';
    }

    #[Computed]
    public function drfoNotVerified(): bool
    {
        return data_get($this->verificationDetails, 'details.drfo.verification_status') === 'NOT_VERIFIED';
    }

    #[Computed]
    public function dracsDeathNotVerified(): bool
    {
        return data_get($this->verificationDetails, 'details.dracs_death.verification_status') === 'NOT_VERIFIED';
    }

    #[Computed]
    public function hasVerificationWarnings(): bool
    {
        return $this->drfoNotVerified || $this->dracsDeathNotVerified;
    }

    /**
     * This method is automatically called by Livewire when the $stream changes.
     * We need to clear $reason so as not to send "Confirmed death" for "Name change".
     */
    public function updatedStream(): void
    {
        $this->reason = '';
    }

    /**
     * Returns a list of available reasons depending on the thread selected.
     */
    #[Computed]
    public function availableReasons(): array
    {
        if ($this->stream === 'dracs_name_change') {
            return ['MANUAL'];
        }

        return [
            'MANUAL_CONFIRMED',
            'MANUAL_NOT_CONFIRMED',
        ];
    }

    public function loadVerificationDetails(): void
    {
        try {
            $response = EHealth::party()->getDetails($this->party->uuid);
            $this->verificationDetails = $response->validate();
        } catch (\Exception $e) {
            $this->dispatch('flashMessage', [
                'message' => __('forms.verification_data_upload_error'),
                'type' => 'error'
            ]);
            Log::error('Failed to load verification details', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handles the form submission to update the verification status.
     */
    public function updateStatus(): void
    {
        $this->validate(
            [
                'stream' => 'required|string|in:dracs_death,dracs_name_change',
                'status' => 'required|string',
                'reason' => 'required|string',
                'comment' => 'required|string|min:5',
            ]
        );

        $payload = [
            $this->stream => [
                'verification_status' => $this->status,
                'verification_reason' => $this->reason,
                'verification_comment' => $this->comment,
            ]
        ];

        try {
            EHealth::party()->update($this->party->uuid, $payload);

            $this->dispatch('flashMessage', [
                'message' => __('party_verification.messages.update_success') ?? 'Статус успішно оновлено.',
                'type' => 'success'
            ]);

            $this->loadVerificationDetails();
            $this->dispatch('status-updated-close-modal');

        } catch (EHealthValidationException $e) {
            Log::error('[PARTY UPDATE DEBUG] eHealth API returned a validation error.', [
                'party_uuid' => $this->party->uuid,
                'payload_sent' => $payload,
                'validation_errors' => $e->getErrors(),
            ]);

            $this->dispatch('flashMessage', [
                'message' => 'Помилка валідації від ЕСОЗ: ' . $e->getTranslatedMessage(),
                'type' => 'error'
            ]);

        } catch (EHealthResponseException $e) {
            Log::error('[PARTY UPDATE DEBUG] eHealth API returned an error response.', [
                'party_uuid' => $this->party->uuid,
                'status_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ]);

            $this->dispatch('flashMessage', [
                'message' => 'Помилка від ЕСОЗ: ' . $e->getMessage(),
                'type' => 'error',
                'persistent' => true
            ]);
            $this->dispatch('status-updated-close-modal');

        } catch (\Exception $e) {
            Log::error('[PARTY UPDATE DEBUG] Request failed with a generic error.', [
                'party_uuid' => $this->party->uuid,
                'error_message' => $e->getMessage(),
            ]);

            $this->dispatch('flashMessage', [
                'message' => 'Виникла неочікувана помилка: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            $this->dispatch('status-updated-close-modal');
        }
    }

    public function render()
    {
        return view('livewire.party.party-verify');
    }
}
