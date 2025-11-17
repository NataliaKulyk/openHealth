<?php

declare(strict_types=1);

namespace App\Traits;

use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use Illuminate\Support\Facades\Log;
use Throwable;

trait ProcessesPartyVerificationResponses
{
    /**
     * Processes party verification statuses using an optimized upsert approach.
     * We provide all NOT NULL columns (e.g., last_name, first_name) to the upsert data array.
     * This prevents 'NOT NULL violation' errors in the edge case where 'upsert'
     * attempts an INSERT (due to a race condition or other anomaly) instead of an UPDATE.
     *
     * @param  EHealthResponse  $response  The API response object.
     * @param  LegalEntity  $legalEntity  The legal entity context.
     * @return void
     * @throws Throwable If the upsert operation fails.
     */
    private function processPartyVerificationResponse(EHealthResponse $response, LegalEntity $legalEntity): void
    {
        $validatedData = $response->validate();

        $eHealthStatuses = $response->map($validatedData);

        if (empty($eHealthStatuses)) {
            Log::info("No party verification updates received from eHealth.");

            return;
        }

        $partyUuids = array_keys($eHealthStatuses);
        $localParties = Party::whereIn('uuid', $partyUuids)
            ->with('users')
            ->get()
            ->keyBy('uuid');

        if ($localParties->isEmpty()) {
            Log::info("No local parties found matching the UUIDs from eHealth.", ['uuids_from_ehealth' => $partyUuids]);

            return;
        }

        Log::info("Found " . $localParties->count() . " local parties to check against eHealth statuses.");

        $upsertData = [];
        foreach ($eHealthStatuses as $uuid => $newStatusItem) {
            $party = $localParties->get($uuid);
            if ($party) {

                $newStatuses = [
                    'verification_status' => data_get($newStatusItem, 'verification_status'),
                    'drfo_status' => data_get($newStatusItem, 'details.drfo.verification_status'),
                    'dracs_death_status' => data_get($newStatusItem, 'details.dracs_death.verification_status'),
                    'mvs_passport_status' => data_get($newStatusItem, 'details.mvs_passport.verification_status'),
                    'dms_passport_status' => data_get($newStatusItem, 'details.dms_passport.verification_status'),
                    'dracs_name_change_status' => data_get($newStatusItem, 'details.dracs_name_change.verification_status'),
                ];

                $isChanged = $party->verification_status !== $newStatuses['verification_status']
                    || $party->drfo_status !== $newStatuses['drfo_status']
                    || $party->dracs_death_status !== $newStatuses['dracs_death_status']
                    || $party->mvs_passport_status !== $newStatuses['mvs_passport_status']
                    || $party->dms_passport_status !== $newStatuses['dms_passport_status']
                    || $party->dracs_name_change_status !== $newStatuses['dracs_name_change_status'];

                if ($isChanged) {
                    $upsertData[] = array_merge(
                        [
                            'uuid' => $uuid,
                            'last_name' => $party->last_name,
                            'first_name' => $party->first_name,
                        ],
                        $newStatuses
                    );
                }
            }
        }

        /**
         * Step 4: Perform the 'upsert'.
         */
        $successfullyUpdatedCount = 0;
        if (!empty($upsertData)) {
            Log::info("Attempting upsert for " . count($upsertData) . " parties.");

            try {
                Party::upsert(
                    values: $upsertData,
                    uniqueBy: ['uuid'],
                    update: [
                                'verification_status',
                                'drfo_status',
                                'dracs_death_status',
                                'mvs_passport_status',
                                'dms_passport_status',
                                'dracs_name_change_status',
                            ]
                );

                $successfullyUpdatedCount = count($upsertData);
                Log::info("[UPSERT SUCCEEDED] Upsert finished (potentially updated {$successfullyUpdatedCount} records).");

            } catch (Throwable $e) {
                Log::error('[UPSERT FAILED] The upsert call failed.', [
                    'error' => $e->getMessage(),
                    'first_item_passed_to_upsert' => $upsertData[0] ?? 'empty'
                ]);
                throw $e;
            }

        } else {
            Log::info("No status changes detected. Skipping upsert.");
        }

        /**
         * Step 5: Define and send notifications.
         */
        foreach ($localParties as $uuid => $party) {
            $newOverallStatus = $eHealthStatuses[$uuid]['verification_status'] ?? null;
            $oldStatus = $party->verification_status;

            if ($newOverallStatus && $oldStatus === 'VERIFIED' && $newOverallStatus !== 'VERIFIED') {
                $usersToNotify = $party->users;
                foreach ($usersToNotify as $userToNotify) {
                    Log::info("Notifying user about status change.", ['user_id' => $userToNotify->id, 'party_uuid' => $uuid, 'old_status' => $oldStatus, 'new_status' => $newOverallStatus]);
                    $userToNotify->notify(new PartyVerificationStatusChanged($party, $newOverallStatus, $legalEntity));
                }
            }
        }

        $context = method_exists($this, 'job') ? '[Job]' : '[Listener]';
        Log::info("{$context} Verification status processing finished. {$successfullyUpdatedCount} records were targetted by the update operation.");
    }
}
