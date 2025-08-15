<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use App\Classes\eHealth\EHealth;
use Exception;
use Livewire\Component;
use App\Models\Division;
use App\Traits\FormTrait;
use App\Models\LegalEntity;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DivisionIndex extends Component
{
    use WithPagination;
    use FormTrait;

    public ?array $working_hours = [
        'mon' => 'Понеділок',
        'tue' => 'Вівторок',
        'wed' => 'Середа',
        'thu' => 'Четвер',
        'fri' => 'П’ятниця',
        'sat' => 'Субота',
        'sun' => 'Неділя',
    ];

    public ?array $tableHeaders = [];

    public string $mode = 'default';

    public array $dictionaryNames = [
        'DIVISION_TYPE'
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->tableHeaders();
        $this->getDictionary();
    }

    #[On('refreshPage')]
    public function refreshPage(): void
    {
        $this->dispatch('$refresh');
    }

    public function tableHeaders(): void
    {
        $this->tableHeaders = [
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
     */
    public function sync(): void
    {
        $response = null;

        try {
            $response = EHealth::division()->getMany();

        } catch (Exception $err) {
            Log::error(self::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));
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
                throw new Exception('response_error ' . $response->body());
            }
        } catch (Exception $err) {
            Log::error(self::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);
        } catch (Exception $err) {
            Log::error(self::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Це місце надання послуг не вдалось активувати'));
        }
    }

    /**
     * Set 'INACTIVE' action status for specified division
     *
     * @param \App\Models\Division $division
     *
     * @return void
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
                throw new Exception('response_error ' . $response->body());
            }
        } catch (Exception $err) {
            Log::error(self::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Помилка при обробці запиту до сервера'));
        }

        $responseData = $response->getData();

        try {
            Repository::division()->setAction($division, $responseData['status']);
        } catch (Exception $err) {
            Log::error(self::class . ':deactivate:', ['message' => $err->getMessage()]);

            session()->flash('error', _('Це місце надання послуг не вдалось деактивувати'));
        }
    }

    public function render(): View
    {
        $perPage = config('pagination.per_page');
        $divisions = legalEntity()?->divisions()->orderBy('uuid')->paginate($perPage);

        return view('livewire.division.division-form', compact('divisions'));
    }
}
