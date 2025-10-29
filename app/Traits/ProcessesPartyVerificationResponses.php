<?php

declare(strict_types=1);

namespace App\Traits;

use App\Classes\eHealth\EHealthResponse;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

trait ProcessesPartyVerificationResponses
{
    /**
     * Processes party verification statuses based on eHealth data using individual updates within a transaction.
     * This method avoids using 'upsert' due to observed issues where it attempted INSERT instead of UPDATE,
     * leading to NOT NULL constraint violations. Using individual updates ensures reliability and data integrity.
     *
     * This method performs the following steps:
     * 1. Fetches the current state (status and 'users' relation) of relevant parties from the local DB.
     * 2. Prepares data containing only the UUID and new status for parties whose status has changed.
     * 3. Executes individual UPDATE queries within a database transaction for each changed party. This approach
     * is safe against race conditions (where a party might be deleted between read and write) as
     * UPDATE will simply affect 0 rows for a non-existent UUID without error.
     * 4. Iterates through the *original* local party data (fetched in step 1) to compare
     * old statuses with the new statuses from eHealth, sending notifications to all associated users
     * only when a party's status specifically changes *from* 'VERIFIED'.
     *
     * @param  EHealthResponse  $response  The API response object containing verification statuses from eHealth.
     * @param  LegalEntity  $legalEntity  The legal entity context, used for notifications.
     * @return void
     * @throws \Throwable If the database transaction fails.
     */
    private function processPartyVerificationResponse(EHealthResponse $response, LegalEntity $legalEntity): void
    {
        $validatedData = $response->validate();
        // $eHealthStatuses is a collection keyed by party UUID: ['uuid' => 'STATUS_FROM_EHEALTH']
        $eHealthStatuses = $response->map($validatedData);

        if (empty($eHealthStatuses)) {
            Log::info("No party verification updates received from eHealth.");

            return;
        }

        /**
         * Step 1: Fetch the current state of relevant parties from the local database.
         * We load the 'users' relation eagerly to avoid N+1 queries later when sending notifications.
         * The result is keyed by UUID for quick lookups.
         *
         * @var Collection<string, Party> $localParties
         */
        $partyUuids = array_keys($eHealthStatuses);
        $localParties = Party::whereIn('uuid', $partyUuids)
            ->with('users') // Eager load 'users' (plural) relation
            ->get()
            ->keyBy('uuid'); // Key by UUID for efficient access

        if ($localParties->isEmpty()) {
            Log::info("No local parties found matching the UUIDs from eHealth.", ['uuids_from_ehealth' => $partyUuids]);

            return;
        }
        Log::info("Found " . $localParties->count() . " local parties to check against eHealth statuses.");

        /**
         * Step 2: Prepare data for the bulk update operation.
         * We only include parties whose status has actually changed.
         */
        $updateData = []; // Renamed from $upsertData for clarity
        foreach ($eHealthStatuses as $uuid => $newStatus) {
            $party = $localParties->get($uuid);
            // Include only if the party exists locally and the status is different
            if ($party && $party->verification_status !== $newStatus) {
                $updateData[] = [
                    'uuid' => $uuid,
                    'verification_status' => $newStatus,
                    // 'updated_at' removed as the 'parties' table does not have timestamps
                ];
            }
        }

        /**
         * Step 3: Execute the update queries within a database transaction.
         * This uses individual UPDATE statements for safety against race conditions and
         * avoids the problematic behavior observed with 'upsert' in this context.
         */
        $successfullyUpdatedCount = 0;
        if (!empty($updateData)) {
            Log::info("Performing updates for " . count($updateData) . " parties using foreach in transaction.");

            DB::transaction(function () use ($updateData, &$successfullyUpdatedCount) {
                foreach ($updateData as $data) {
                    // Update each party individually based on its UUID.
                    // If the party was deleted between Step 1 and now, this UPDATE
                    // will simply affect 0 rows and continue without error.
                    $affectedRows = Party::where('uuid', $data['uuid'])->update(
                        [
                            'verification_status' => $data['verification_status'],
                        ]
                    );
                    $successfullyUpdatedCount += $affectedRows;
                }
            });

        } else {
            Log::info("No status changes detected. Skipping update.");
        }

        /**
         * Step 4: Determine and send notifications based on specific status changes.
         * We iterate through the original $localParties collection (which still holds the OLD statuses)
         * and compare them with the $newStatus obtained from eHealth.
         */
        foreach ($localParties as $uuid => $party) {
            $newStatus = $eHealthStatuses[$uuid] ?? null;
            $oldStatus = $party->verification_status; // The status BEFORE the update

            // Send notification ONLY if the status changed FROM 'VERIFIED' TO something else
            if ($newStatus && $oldStatus === 'VERIFIED' && $newStatus !== 'VERIFIED') {
                $usersToNotify = $party->users; // Get the collection of associated users
                foreach ($usersToNotify as $userToNotify) {
                    Log::info("Notifying user about status change.", ['user_id' => $userToNotify->id, 'party_uuid' => $uuid, 'old_status' => $oldStatus, 'new_status' => $newStatus]);
                    // Pass the $party object (with old data but correct relations), the new status, and legal entity
                    $userToNotify->notify(new PartyVerificationStatusChanged($party, $newStatus, $legalEntity));
                }
            }
        }

        $context = method_exists($this, 'job') ? '[Job]' : '[Listener]';
        Log::info("{$context} Update process finished. {$successfullyUpdatedCount} party verification records were updated.");
    }
}
