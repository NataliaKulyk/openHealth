<?php

declare(strict_types=1);

namespace App\Enums\Party;

use Illuminate\Support\Facades\Lang;

/**
 * Displays the verification status for an individual "DRACS (death)" stream.
 *
 * @see \App\Livewire\Party\PartyVerify
 */
enum DracsDeathStatus: string
{
    case VERIFIED = 'VERIFIED';
    case NOT_VERIFIED = 'NOT_VERIFIED';
    case VERIFICATION_NEEDED = 'VERIFICATION_NEEDED';

    /**
         * Returns the translated label for status.
         */
    public function label(): string
    {

        return Lang::get('general.verification_statuses.' . $this->value);
    }
}
