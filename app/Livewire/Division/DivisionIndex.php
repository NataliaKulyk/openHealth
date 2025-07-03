<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use App\Models\Division;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Repositories\AddressRepository;
use App\Livewire\Division\Api\DivisionRequestApi;

class DivisionIndex extends Component
{
    use WithPagination;
    use FormTrait;

    protected ?AddressRepository $addressRepository;

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

    public function boot(AddressRepository $addressRepository): void
    {
        $this->addressRepository = $addressRepository;
    }

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

    public function syncDivisions(): void
    {
        $syncDivisions = DivisionRequestApi::syncDivisionRequest(legalEntity()->uuid);

        $this->syncDivisionsSave($syncDivisions);

        $this->dispatch('refreshPage');
        $this->dispatch('flashMessage', ['message' => __('Інформацію успішно оновлено'), 'type' => 'success']);
    }

    public function syncDivisionsSave($responses): void
    {
        DB::transaction(function () use ($responses) {
            foreach ($responses as $response) {
                $addressData = $response['addresses'];

                unset($response['addresses']);
                unset($response['dls_id']);
                unset($response['dls_verified']);

                $response['phones'] = $response['phones'][0];

                $division = Division::firstOrNew(['uuid' => $response['id']]);

                unset($response['id']);

                $division->fill($response);
                $division->setAttribute('legal_entity_uuid', $response['legal_entity_id']);
                $division->setAttribute('external_id', $response['external_id']);
                $division->setAttribute('status', $response['status']);

                $savedDivision = legalEntity()?->division()->save($division);

                $this->addressRepository->addAddresses($savedDivision, $addressData);
            }
        });
    }

    public function activate(Division $division): void
    {
        DivisionRequestApi::activateDivisionRequest($division['uuid']);

        $division->setAttribute('status', 'ACTIVE');
        $division->save();

        $this->dispatch('refreshPage');
    }

    public function deactivate(Division $division): void
    {
        DivisionRequestApi::deactivateDivisionRequest($division['uuid']);

        $division->setAttribute('status', 'INACTIVE');
        $division->save();

        $this->dispatch('refreshPage');
    }

    public function render(): View
    {
        $perPage = config('pagination.per_page');
        $divisions = legalEntity()?->division()->orderBy('uuid')->paginate($perPage);

        return view('livewire.division.division-form', compact('divisions'));
    }
}
