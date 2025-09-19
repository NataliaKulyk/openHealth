<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Classes\Cipher\Api\CipherRequest;
use App\Classes\Cipher\Exceptions\CipherApiException;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
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

        [$lastName, $firstName, $secondName] = explode(' ', $ownerFullName);

        // Search for party
        $party = Party::whereNull('user_id')
            ->where('tax_id', $taxId)
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower($lastName)])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower($firstName)])
            ->whereRaw('LOWER(TRIM(second_name)) = ?', [mb_strtolower($secondName)])
            ->first();

        if (!$party) {
            Session::flash('error', 'Співпадінь не знайдено, зверніться до адміністратора');

            return;
        }

        // Associate current User with Party, also set email
        $party->update(['user_id' => Auth::id(), 'email' => Auth::user()->email]);

        // Link all employees of this Party to User
        $party->employees()->update(['user_id' => Auth::id()]);

        $legalEntityUuid = Session::pull('selected_legal_entity_uuid');
        $legalEntity = LegalEntity::whereUuid($legalEntityUuid)->firstOrFail();

        $this->redirectRoute('dashboard', ['legalEntity' => $legalEntity], navigate: true);
    }
}
