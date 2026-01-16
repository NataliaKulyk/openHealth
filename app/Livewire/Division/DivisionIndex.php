<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Throwable;
use Exception;
use App\Models\Division;
use Illuminate\Bus\Batch;
use App\Jobs\DivisionSync;
use Livewire\WithPagination;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use App\Notifications\SyncNotification;
use App\Livewire\Division\Trait\HasAction;
use Illuminate\Http\Client\ConnectionException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;

class DivisionIndex extends DivisionComponent
{
    use WithPagination;
    use HasAction;

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
    public function updatingDivisionFormSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Synchronize all the Divisions with stored on the eHealths side
     *
     * @return void
     * @throws Exception|ConnectionException
     */
    public function sync(): void
    {
        if (Auth::user()->cannot('viewAny', Division::class)) {
            Session::flash('error', 'У вас немає дозволу на синхронізацію місць надання послуг');

            return;
        }

        $syncQuery = [
            'page' => 1,
            'per_page' => config('ehealth.api.max_per_page')
        ];

        try {
            $response = EHealth::division()->getMany(query: $syncQuery);

            $divisions = $response->validate();

            Repository::division()->saveDivisionsList($divisions);
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':createDivision', ['error' => $err->getDetails()]);
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

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            $token = session()->get(config('ehealth.api.oauth.bearer_token'));
            $user = Auth::user();

            Bus::batch([
                new DivisionSync(
                    legalEntity: legalEntity(),
                    page: 2,
                    standalone: true, // Sync only divisions (without healthcare services)
                    nextEntity: null
                )
            ])
                ->withOption('legal_entity_id', legalEntity()->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(function (Batch $batch) use ($user) {
                    $user->notify(new SyncNotification('division', 'complete'));
                })->catch(callback: function (Batch $batch, Throwable $e) use ($user) {
                    Log::error('Division sync batch failed.', [
                        'batch_id' => $batch->id,
                        'exception' => $e
                    ]);

                    $user->notify(new SyncNotification('division', 'failed'));
                })
                ->onQueue('sync')
                ->name('DivisionSync')
                ->dispatch();

                session()->flash('success', __('Синхронізація запущена у фоновому режимі'));
        } else {
            session()->flash('success', __('Інформацію успішно оновлено'));
        }

        $this->redirect(route('division.index', [legalEntity()]), navigate: true);

        return;
    }

    public function render(): View
    {
        $perPage = config('pagination.per_page');

        $divisions = legalEntity()
            ?->divisions()
            ->orderBy('uuid')
            ->search($this->divisionForm->search)
            ->paginate($perPage);

        return view('livewire.division.division-index', compact('divisions'));
    }
}
