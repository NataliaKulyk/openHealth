<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\License;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LicenseIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public function mount(LegalEntity $legalEntity): void
    {
    }

    #[Computed]
    public function licenses(): LengthAwarePaginator
    {
        $allItems = License::where('legal_entity_id', legalEntity()->id)
            ->select(['id', 'type', 'active_from_date', 'expiry_date', 'what_licensed', 'is_primary'])
            ->get();

        // Pagination
        $perPage = config('pagination.per_page');
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    public function render(): View
    {
        return view('livewire.license.license-index', ['licenses' => $this->licenses]);
    }

    /**
     * Synchronize licenses with eHealth, the method overrides existing licenses if uuid is the same
     *
     * @return void
     */
    public function sync(): void
    {
        try {
            $response = EHealth::license()->getMany();

        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting licenses');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting licenses');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        $licences = $response->validate();

        foreach ($licences as $number => $license) {
            unset($licences[$number]['legal_entity_uuid']);
            $licences[$number]['legal_entity_id'] = legalEntity()->id;
        }

        try {
            License::upsert($licences, uniqueBy: ['uuid'], update: new License()->getFillable());
        } catch (Exception $exception) {
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');
            $this->logDatabaseErrors($exception, 'Error while synchronizing licenses with eHealth: ');

            return;
        }

        if ($response->isNotLast()) {
            // TODO run
        }

        Session::flash('success', __('licenses.sync_success'));
    }
}
