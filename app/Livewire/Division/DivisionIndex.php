<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Exception;
use App\Models\Division;
use Livewire\WithPagination;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;

class DivisionIndex extends DivisionComponent
{
    use WithPagination;

    #[Computed]
    public function tableHeaders(): array
    {
        return [
            __('ID E-health '),
            __('forms.name'),
            __('forms.type'),
            __('Телефон'),
            __('Email'),
            __('Статус'),
            __('forms.action'),
        ];
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

            if (! $response->successful()) {
                $this->logEHealthError($response,  'EHealth\'s Request response_error');

                throw new ConnectionException('Cannot retrieve divisions list. See errors message upper...');
            }
        } catch (ConnectionException $err) {
            Log::channel('e_health_errors')->error(static::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));

            return;
        }

        $divisions = $response->validate();

        try {
            Repository::division()->saveDivisionsList($divisions);
        } catch (Exception $err) {
            Log::error(static::class . ': [syncDivisions]: ', ['error' => $err->getMessage()]);

            session()->flash('error', __('Помилка синхронізації. Зверніться до адміністратора.'));

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
     * Set 'ACTIVE' action status for specified division
     *
     * @param \App\Models\Division $division
     *
     * @return void
     *
     * @throws Exception|ConnectionException
     */
    public function activate(Division $division): void
    {
        if (Auth::user()->cannot('activate', $division)) {
            session()->flash('error', __('У вас немає дозволу на деактивацію цього місця надання послуг'));

            return;
        }

        try {
            $response = EHealth::division()->activate($division->uuid);

            if (! $response->successful()) {
                $this->logEHealthError($response,  'EHealth\'s Request response_error');

                throw new ConnectionException('Wrong activation request. See errors message upper...');
            }
        } catch (ConnectionException $err) {
            Log::channel('e_health_errors')->error(static::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));

            return;
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);
        } catch (Exception $err) {
            Log::error(static::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Це місце надання послуг не вдалось активувати'));
        }
    }

    /**
     * Set 'INACTIVE' action status for specified division
     *
     * @param \App\Models\Division $division
     *
     * @return void
     *
     * @throws Exception|ConnectionException
     */
    public function deactivate(Division $division): void
    {
        if (Auth::user()->cannot('deactivate', $division)) {
            session()->flash('error', __('У вас немає дозволу на деактивацію цього місця надання послуг'));

            return;
        }

        try {
            $response = EHealth::division()->deactivate($division->uuid);

            if (! $response->successful()) {
                $this->logEHealthError($response,  'EHealth\'s Request response_error');

                throw new ConnectionException('Wrong deactivation request. See errors message upper...');
            }
        } catch (ConnectionException $err) {
            Log::channel('e_health_errors')->error(static::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));

            return;
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);
        } catch (Exception $err) {
            Log::error(static::class . ':deactivate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Це місце надання послуг не вдалось деактивувати'));
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render(): View
    {
        $perPage = config('pagination.per_page');
        $divisions = legalEntity()?->divisions()->orderBy('uuid')->paginate($perPage);

        return view('livewire.division.division-index', compact('divisions'));
    }
}
