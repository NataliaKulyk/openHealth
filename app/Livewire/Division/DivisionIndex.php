<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Throwable;
use Exception;
use Livewire\WithPagination;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use App\Livewire\Division\Trait\HasAction;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;

class DivisionIndex extends DivisionComponent
{
    use WithPagination,
        HasAction;

    #[Computed]
    public function tableHeaders(): array
    {
        return [
            __('forms.name'),
            __('forms.type'),
            __('Телефон'),
            __('Email'),
            __('Статус'),
            __('forms.action'),
        ];
    }

    public function mount(): void
    {
        $this->setDictionary();
    }

    /**
     * Resets the pagination when the search term is updated.
     *
     * It ensures that when a user starts searching, the pagination
     * is reset to the first page to show the most relevant results.
     *
     * @return void
     */
    public function updatingDivisionFormSearch()
    {
        $this->resetPage();
    }

    /**
     * Synchronize all the Divisisons with stored on the eHealths side
     *
     * @return void
     *
     * @throws Exception|ConnectionException
     */
    public function sync(): void
    {
        $response = null;

        try {
            $response = EHealth::division()->getMany();

            $divisions = $response->validate();

            Repository::division()->saveDivisionsList($divisions);
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':createDivision', ['error' => $err->getMessage()]);
            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':createDivision', ['error' => $err->getDetails()]);

            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (Throwable $err) {
            Log::channel('db_errors')->error(static::class . ': [syncDivisions]: ', ['error' => $err->getMessage()]);

            session()->flash('error', __('divisions.request.sync.errors.fail'));

            return;
        }

        if ($response?->isNotLast()) {
            // TODO run
            dd('Multi-Paging detected', $response->getPaging());
            // SyncDivisionsListJob::dispatch(legalEntity(), 2); // page starts from number 2
        }

        session()->flash('success', __(__('Інформацію успішно оновлено')));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        $perPage = config('pagination.per_page');

        $divisions= legalEntity()
            ?->divisions()
            ->orderBy('uuid')
            ->search($this->divisionForm->search)
            ->paginate($perPage);

        return view('livewire.division.division-index', compact('divisions'));
    }
}
