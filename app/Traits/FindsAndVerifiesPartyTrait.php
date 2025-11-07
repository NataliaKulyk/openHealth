<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Relations\Party;
use Illuminate\Support\Facades\Log;

trait FindsAndVerifiesPartyTrait
{
    /**
     * Finds a Party by Tax ID, then performs a "smart" validation of the full name.
     *
     * @param  string  $taxId  The individual's Tax ID (RNOKPP).
     * @param  string  $lastName  Last name.
     * @param  string  $firstName  First name.
     * @param  string|null  $secondName  Patronymic/middle name.
     * @return Party|null Returns the Party model on success, or null on failure.
     */
    protected function findAndVerifyParty(string $taxId, string $lastName, string $firstName, ?string $secondName): ?Party
    {
        // 1. Find the Party by Tax ID (this is our anchor).
        $party = Party::where('tax_id', $taxId)->first();

        if (!$party) {
            Log::warning('[FindsAndVerifiesPartyTrait] Party not found by Tax ID.', ['tax_id' => $taxId]);

            return null;
        }

        // 2. Normalize names from the DB and the input data.
        $db_last = mb_strtolower(trim($party->last_name));
        $input_last = mb_strtolower(trim($lastName));

        $db_first = mb_strtolower(trim($party->first_name));
        $input_first = mb_strtolower(trim($firstName));

        // 3. Strict check: Last Name and First Name MUST match.
        if ($db_last !== $input_last || $db_first !== $input_first) {
            Log::warning('[FindsAndVerifiesPartyTrait] Last Name or First Name mismatch.', [
                'tax_id' => $taxId,
                'db_name' => "$party->last_name $party->first_name",
                'input_name' => "$lastName $firstName",
            ]);

            return null;
        }

        // 4. Flexible check for patronymic (middle name).
        $db_second = mb_strtolower(trim($party->second_name ?? ''));
        $input_second = mb_strtolower(trim($secondName ?? ''));

        // Fail ONLY IF both patronymics exist but do not match.
        if (!empty($db_second) && !empty($input_second) && $db_second !== $input_second) {
            Log::warning('[FindsAndVerifiesPartyTrait] Patronymic mismatch.', [
                'tax_id' => $taxId,
                'db_second' => $party->second_name,
                'input_second' => $secondName,
            ]);

            return null;
        }

        // 5. Success.
        Log::info('[FindsAndVerifiesPartyTrait] Party found and verified.', ['party_id' => $party->id]);

        return $party;
    }
}
