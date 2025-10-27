<?php

namespace App\Traits;

use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;

trait ProcessesPartyVerificationResponses
{
    private function processPartyVerificationResponse(
        EHealthResponse $response,
        LegalEntity $legalEntity
    ): void
    {
        $validatedData = $response->validate();
        $updates       = $response->map($validatedData);

        if (empty($updates)) {
            return;
        }

        $successfullyUpdatedCount = 0;
        foreach ($updates as $uuid => $newStatus) {
            $party = Party::where('uuid', $uuid)->first();
            if (!$party || $party->verification_status === $newStatus) {
                continue;
            }

            $oldStatus = $party->verification_status;
            $party->update(['verification_status' => $newStatus]);
            $successfullyUpdatedCount++;

            if ($oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                if ($userToNotify = $party->user) {
                    $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus, $legalEntity));
                }
            }
        }
    }
}
