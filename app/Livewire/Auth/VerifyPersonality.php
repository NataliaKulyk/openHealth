<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Classes\Cipher\Api\CipherRequest;
use App\Classes\Cipher\Exceptions\CipherApiException;
use App\Events\EhealthUserVerified;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Traits\FindsAndVerifiesPartyTrait;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use JsonException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.guest')]
class VerifyPersonality extends Component
{
    use WithFileUploads;
    use FindsAndVerifiesPartyTrait;

    #[Validate(['required', 'string'])]
    public string $knedp;

    #[Validate(['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'])]
    public ?TemporaryUploadedFile $keyContainerUpload = null;

    #[Validate(['required', 'string'])]
    public string $password;

    public function login(): void
    {
        $this->validate();

        try {
            $response = new CipherRequest()->getPersonalData($this->knedp, $this->keyContainerUpload, $this->password);
        } catch (ConnectionException|CipherApiException $exception) {
            Log::channel('api_errors')->error($exception->getMessage(), ['context' => $exception->getContext()]);
            Session::flash('error', 'Сталася помилка під час завантаження ключа');

            return;
        } catch (JsonException $exception) {
            Log::channel('api_errors')->error($exception->getMessage());
            Session::flash('error', 'Сталася помилка під час завантаження ключа');

            return;
        }

        $ownerFullName = $response?->getOwnerFullName();
        $taxId = $response?->getTaxId();

        // Safely parse the full name from the KEP (digital signature).
        // This handles extra whitespace and limits the result to 3 parts (Last, First, Second).
        $nameParts = preg_split('/\s+/', trim($ownerFullName), 3, PREG_SPLIT_NO_EMPTY);
        $lastName = $nameParts[0] ?? null;
        $firstName = $nameParts[1] ?? null;
        $secondName = $nameParts[2] ?? null;

        // If the KEP full name is invalid (e.g., only one word or empty), we can't proceed.
        if (!$lastName || !$firstName) {
            Log::warning('KEP parsing failed', ['name' => $ownerFullName]);
            Session::flash('error', __('errors.kep_name_parse_failed'));

            return;
        }

        $party = $this->findAndVerifyParty($taxId, $lastName, $firstName, $secondName);

        if (!$party) {

            Session::flash('error', __('errors.kep_name_mismatch'));

            return;
        }

        $user = Auth::user();

        /*
         * This check (`!$user->partyId`) is crucial for idempotency.
         * It handles scenarios where a user might land on this verification
         * page even after they are already linked to a Party.
         *
         * How can this happen?
         * 1. User verifies successfully, `$user->partyId` is set.
         * 2. They are redirected to the dashboard.
         * 3. They use the browser's "Back" button, which re-loads this page.
         * 4. They (mistakenly) try to submit the form a second time.
         *
         * This `if` block prevents our code from trying to re-link an
         * already-linked user.
         */
        if (!$user->partyId) {
            Log::info('[VerifyPersonality] Успішна верифікація КЕП. Прив\'язуємо User до Party.', [
                'user_id' => $user->id,
                'party_id_to_link' => $party->id,
            ]);
            $user->partyId = $party->id;
            $user->save();
        }

        $legalEntityUuid = Session::pull('selected_legal_entity_uuid');
        $legalEntity = LegalEntity::whereUuid($legalEntityUuid)->firstOrFail();

        $affectedRows = $party->employees()
            ->where('legal_entity_id', $legalEntity->id)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        if ($affectedRows === 0) {
            $isAlreadyVerified = $party->employees()
                ->where('legal_entity_id', $legalEntity->id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$isAlreadyVerified) {
                Session::flash('error', 'Для вашого профілю не знайдено активних посад у цьому закладі. Зверніться до адміністратора.');

                return;
            }
        }

        EhealthUserVerified::dispatch($user, $legalEntity->id);

        $this->redirectRoute('dashboard', ['legalEntity' => $legalEntity], navigate: true);
    }
}
